<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Inventory;

use Database\Seeders\RolePermissionSeeder;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryItemRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryStockLevelRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\ProductRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductVariantListTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_rows_identify_and_filter_sibling_variants(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $shop = ShopRecord::factory()->create();

        $phone = ProductRecord::factory()->create(['brand' => 'Apple', 'model' => 'iPhone SE']);
        $black = SkuRecord::factory()->create([
            'product_id' => $phone->id,
            'sku_code' => 'IPH-SE-BLK',
            'attributes' => ['color' => 'Black', 'storage' => '128GB'],
        ]);
        $blue = SkuRecord::factory()->create([
            'product_id' => $phone->id,
            'sku_code' => 'IPH-SE-BLU',
            'attributes' => ['color' => 'Blue', 'storage' => '256GB'],
        ]);
        $single = SkuRecord::factory()->create([
            'product_id' => ProductRecord::factory()->create(['brand' => 'Anker', 'model' => 'Charger'])->id,
            'sku_code' => 'ANK-CHG-001',
            'attributes' => [],
            'is_serialized' => false,
        ]);
        InventoryItemRecord::factory()->create([
            'sku_id' => $black->id,
            'current_shop_id' => $shop->id,
            'status' => 'available',
        ]);
        InventoryItemRecord::factory()->create([
            'sku_id' => $black->id,
            'current_shop_id' => $shop->id,
            'status' => 'reserved',
        ]);
        InventoryStockLevelRecord::factory()->create([
            'sku_id' => $single->id,
            'shop_id' => $shop->id,
            'on_hand' => 10,
            'reserved' => 2,
        ]);

        $all = $this->actingAs($owner, 'staff')->get('/admin/inventory/products');

        $all->assertInertia(fn ($page) => $page
            ->where('products.data.0.id', $black->id)
            ->where('products.data.0.variant_label', 'Black · 128GB')
            ->where('products.data.0.variant_position', 1)
            ->where('products.data.0.variant_count', 2)
            ->where('products.data.0.stock_available', 1)
            ->where('products.data.0.stock_reserved', 1)
            ->where('products.data.1.id', $blue->id)
            ->where('products.data.1.variant_position', 2)
            ->where('products.data.1.variant_count', 2)
            ->where('products.data.2.id', $single->id)
            ->where('products.data.2.variant_count', 1)
            ->where('products.data.2.stock_available', 8)
            ->where('products.data.2.stock_reserved', 2));

        $variants = $this->actingAs($owner, 'staff')->get('/admin/inventory/products?variant=variants');
        $variants->assertInertia(fn ($page) => $page
            ->has('products.data', 2)
            ->where('filters.variant', 'variants'));

        $singleProducts = $this->actingAs($owner, 'staff')->get('/admin/inventory/products?variant=single');
        $singleProducts->assertInertia(fn ($page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.id', $single->id));

        $attributeSearch = $this->actingAs($owner, 'staff')->get('/admin/inventory/products?search=256GB');
        $attributeSearch->assertInertia(fn ($page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.id', $blue->id));
    }
}
