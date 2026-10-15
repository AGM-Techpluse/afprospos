<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Sales;

use Database\Seeders\RolePermissionSeeder;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryItemRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryStockLevelRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\ProductRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Checkout search finds one item by name/SKU/IMEI, or browses by category or brand — and never offers repair-only parts. */
class CheckoutSearchFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_category_filter_returns_only_that_category_and_a_brand_filter_only_that_brand(): void
    {
        $cashier = $this->cashier();
        $shop = ShopRecord::factory()->create();

        $phone = $this->sellable($shop, ['brand' => 'Apple', 'model' => 'iPhone 15', 'category' => 'Phones', 'purpose' => 'sale']);
        $case = $this->sellable($shop, ['brand' => 'Anker', 'model' => 'Case', 'category' => 'Accessories', 'purpose' => 'sale']);

        $byCategory = $this->actingAs($cashier, 'staff')
            ->getJson("/admin/sales/search?shop_id={$shop->id}&category=Phones")
            ->assertOk()
            ->json('results');

        $this->assertSame([$phone->id], array_column($byCategory, 'sku_id'));

        $byBrand = $this->actingAs($cashier, 'staff')
            ->getJson("/admin/sales/search?shop_id={$shop->id}&brand=Anker")
            ->assertOk()
            ->json('results');

        $this->assertSame([$case->id], array_column($byBrand, 'sku_id'));
    }

    public function test_repair_only_parts_never_appear_in_checkout_search(): void
    {
        $cashier = $this->cashier();
        $shop = ShopRecord::factory()->create();

        $screen = $this->sellable($shop, ['brand' => 'Apple', 'model' => 'iPhone 13 screen', 'category' => 'Parts', 'purpose' => 'repair_part']);

        $results = $this->actingAs($cashier, 'staff')
            ->getJson("/admin/sales/search?shop_id={$shop->id}&q=screen")
            ->assertOk()
            ->json('results');

        $this->assertNotContains($screen->id, array_column($results, 'sku_id'));
    }

    public function test_searching_by_imei_finds_the_serialized_unit(): void
    {
        $cashier = $this->cashier();
        $shop = ShopRecord::factory()->create();
        $sku = $this->sellable($shop, ['brand' => 'Apple', 'model' => 'iPhone 15', 'category' => 'Phones', 'purpose' => 'sale'], serialized: true);
        InventoryItemRecord::factory()->create([
            'sku_id' => $sku->id,
            'imei' => '356938035601001',
            'current_shop_id' => $shop->id,
            'status' => 'available',
        ]);

        $results = $this->actingAs($cashier, 'staff')
            ->getJson("/admin/sales/search?shop_id={$shop->id}&q=356938035601001")
            ->assertOk()
            ->json('results');

        $this->assertSame([$sku->id], array_column($results, 'sku_id'));
    }

    public function test_a_model_filter_narrows_to_that_model_and_the_model_list_narrows_to_the_picked_category_and_brand(): void
    {
        $cashier = $this->cashier();
        $shop = ShopRecord::factory()->create();

        $iphone15 = $this->sellable($shop, ['brand' => 'Apple', 'model' => 'iPhone 15', 'category' => 'Phones', 'purpose' => 'sale']);
        $iphone14 = $this->sellable($shop, ['brand' => 'Apple', 'model' => 'iPhone 14', 'category' => 'Phones', 'purpose' => 'sale']);
        $this->sellable($shop, ['brand' => 'Anker', 'model' => 'Case', 'category' => 'Accessories', 'purpose' => 'sale']);

        $byModel = $this->actingAs($cashier, 'staff')
            ->getJson("/admin/sales/search?shop_id={$shop->id}&model=iPhone 15")
            ->assertOk()
            ->json('results');

        $this->assertSame([$iphone15->id], array_column($byModel, 'sku_id'));

        $modelsForApplePhones = $this->actingAs($cashier, 'staff')
            ->getJson('/admin/sales/models?category=Phones&brand=Apple')
            ->assertOk()
            ->json('results');

        $this->assertSame(['iPhone 14', 'iPhone 15'], $modelsForApplePhones);
    }

    public function test_search_results_carry_a_variant_label_built_from_the_skus_attributes(): void
    {
        $cashier = $this->cashier();
        $shop = ShopRecord::factory()->create();

        $variant = $this->sellable(
            $shop,
            ['brand' => 'Apple', 'model' => 'iPhone 15', 'category' => 'Phones', 'purpose' => 'sale'],
            attributes: ['color' => 'Black', 'custom' => ['storage' => '128GB']],
        );
        $plain = $this->sellable($shop, ['brand' => 'Anker', 'model' => 'Case', 'category' => 'Accessories', 'purpose' => 'sale']);

        $results = $this->actingAs($cashier, 'staff')
            ->getJson("/admin/sales/search?shop_id={$shop->id}&q=iPhone")
            ->assertOk()
            ->json('results');

        $byId = collect($results)->keyBy('sku_id');
        $this->assertSame('Black · 128GB', $byId[$variant->id]['variant_label']);

        $plainResults = $this->actingAs($cashier, 'staff')
            ->getJson("/admin/sales/search?shop_id={$shop->id}&q=Case")
            ->assertOk()
            ->json('results');

        $this->assertSame('', collect($plainResults)->keyBy('sku_id')[$plain->id]['variant_label']);
    }

    private function cashier(): StaffRecord
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Shop Owner');

        return $cashier;
    }

    /**
     * @param  array<string, mixed>  $product
     * @param  array<string, mixed>  $attributes
     */
    private function sellable(ShopRecord $shop, array $product, bool $serialized = false, array $attributes = []): SkuRecord
    {
        $productRecord = ProductRecord::query()->create($product);

        $sku = SkuRecord::factory()->create([
            'product_id' => $productRecord->id,
            'is_serialized' => $serialized,
            'attributes' => $attributes,
        ]);

        if (! $serialized) {
            InventoryStockLevelRecord::factory()->create(['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 5, 'reserved' => 0]);
        }

        return $sku;
    }
}
