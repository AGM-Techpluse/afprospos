<?php

declare(strict_types=1);

namespace Tests\Feature\Shop;

use Domain\Inventory\Application\Contracts\StorefrontCatalogQuery;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryStockLevelRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\ProductRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_can_browse_the_storefront_without_logging_in(): void
    {
        $shop = ShopRecord::factory()->create(['status' => 'active']);
        $sku = SkuRecord::factory()->nonSerialized()->create();
        $sku->product()->update(['purpose' => 'sale', 'brand' => 'Google', 'model' => 'Pixel 9']);
        InventoryStockLevelRecord::factory()->create(['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 5, 'reserved' => 0]);

        $response = $this->get('/shop');

        // No search/category active — this is the Jumia-style "browse mode" homepage
        // (hero + featured + category rows), not the flat paginated list.
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Shop/Index')
            ->where('products', null)
            ->where('browse.featured.0.brand', 'Google')
            ->where('browse.featured.0.model', 'Pixel 9')
            ->where('browse.featured.0.available_total', 5));
    }

    public function test_a_repair_only_part_never_appears_on_the_storefront(): void
    {
        $sku = SkuRecord::factory()->nonSerialized()->create();
        $sku->product()->update(['purpose' => 'repair_part']);

        $response = $this->get('/shop');

        $response->assertInertia(fn ($page) => $page->where('browse.featured', []));
    }

    public function test_the_catalog_aggregates_stock_across_shops_and_excludes_warehouses(): void
    {
        $mainShop = ShopRecord::factory()->create(['name' => 'Main Shop', 'status' => 'active']);
        $branch = ShopRecord::factory()->create(['name' => 'Ikeja Branch', 'status' => 'active']);
        $warehouse = ShopRecord::factory()->create(['name' => 'Warehouse', 'status' => 'active', 'is_warehouse' => true]);

        $sku = SkuRecord::factory()->nonSerialized()->create();
        $sku->product()->update(['purpose' => 'both']);

        InventoryStockLevelRecord::factory()->create(['sku_id' => $sku->id, 'shop_id' => $mainShop->id, 'on_hand' => 3, 'reserved' => 1]);
        InventoryStockLevelRecord::factory()->create(['sku_id' => $sku->id, 'shop_id' => $branch->id, 'on_hand' => 2, 'reserved' => 0]);
        InventoryStockLevelRecord::factory()->create(['sku_id' => $sku->id, 'shop_id' => $warehouse->id, 'on_hand' => 50, 'reserved' => 0]);

        $response = $this->get("/shop/{$sku->id}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Shop/Show')
            // 2 (3-1) + 2, never the warehouse's 50.
            ->where('product.available_total', 4)
            ->has('product.shops_available', 2)
            ->where('product.shops_available.0.shop_name', 'Main Shop')
            ->where('product.shops_available.0.available', 2));
    }

    public function test_the_category_filter_narrows_the_list(): void
    {
        $phone = SkuRecord::factory()->nonSerialized()->create();
        $phone->product()->update(['purpose' => 'sale', 'category' => 'Phones']);

        $tablet = SkuRecord::factory()->nonSerialized()->create();
        $tablet->product()->update(['purpose' => 'sale', 'category' => 'Tablets']);

        $response = $this->get('/shop?category=Tablets');

        $response->assertInertia(fn ($page) => $page
            ->where('products.total', 1)
            ->where('products.data.0.category', 'Tablets'));
    }

    public function test_a_sku_that_is_not_for_sale_returns_a_404_on_the_detail_page(): void
    {
        $sku = SkuRecord::factory()->nonSerialized()->create();
        $sku->product()->update(['purpose' => 'repair_part']);

        $this->get("/shop/{$sku->id}")->assertNotFound();
    }

    public function test_featured_products_are_the_newest_sellable_ones_only(): void
    {
        $shop = ShopRecord::factory()->create(['status' => 'active']);

        $older = SkuRecord::factory()->nonSerialized()->create();
        $older->product()->update(['purpose' => 'sale', 'created_at' => now()->subDay()]);
        InventoryStockLevelRecord::factory()->create(['sku_id' => $older->id, 'shop_id' => $shop->id, 'on_hand' => 3, 'reserved' => 0]);

        $newer = SkuRecord::factory()->nonSerialized()->create();
        $newer->product()->update(['purpose' => 'both', 'created_at' => now()]);
        InventoryStockLevelRecord::factory()->create(['sku_id' => $newer->id, 'shop_id' => $shop->id, 'on_hand' => 3, 'reserved' => 0]);

        $repairOnly = SkuRecord::factory()->nonSerialized()->create();
        $repairOnly->product()->update(['purpose' => 'repair_part', 'created_at' => now()]);

        $featured = app(StorefrontCatalogQuery::class)->featured(10);

        $this->assertCount(2, $featured);
        $this->assertSame($newer->id, $featured[0]['sku_id']);
        $this->assertSame($older->id, $featured[1]['sku_id']);
    }

    public function test_the_detail_page_lists_sibling_skus_of_the_same_product_as_variants(): void
    {
        $product = ProductRecord::factory()->create(['purpose' => 'sale']);
        $black = SkuRecord::factory()->nonSerialized()->create(['product_id' => $product->id, 'attributes' => ['color' => 'Black']]);
        $blue = SkuRecord::factory()->nonSerialized()->create(['product_id' => $product->id, 'attributes' => ['color' => 'Blue']]);
        $otherProductSku = SkuRecord::factory()->nonSerialized()->create();
        $otherProductSku->product()->update(['purpose' => 'sale']);

        $found = app(StorefrontCatalogQuery::class)->find($black->id);

        $this->assertCount(1, $found['siblings']);
        $this->assertSame($blue->id, $found['siblings'][0]['sku_id']);
        $this->assertSame(['color' => 'Blue'], $found['siblings'][0]['attributes']);
    }
}
