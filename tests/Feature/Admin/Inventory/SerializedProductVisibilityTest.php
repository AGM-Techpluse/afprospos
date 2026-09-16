<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Inventory;

use Database\Seeders\RolePermissionSeeder;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryItemRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A serialized SKU never has an `inventory_stock_levels` row (quantity
 * doesn't apply to an individually-tracked unit) -- its units live in
 * `inventory_items` instead. Both the product detail page and the
 * shop-filtered product list used to only ever look at stockLevels,
 * which made a serialized product with real stock look like it had none.
 */
class SerializedProductVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_product_detail_page_lists_serialized_units_instead_of_an_empty_stock_table(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $shop = ShopRecord::factory()->create();
        $sku = SkuRecord::factory()->create(['is_serialized' => true]);
        InventoryItemRecord::factory()->create([
            'sku_id' => $sku->id,
            'current_shop_id' => $shop->id,
            'imei' => '356938035601009',
            'status' => 'available',
        ]);

        $response = $this->actingAs($owner, 'staff')->get("/admin/inventory/products/{$sku->id}");

        $response->assertInertia(fn ($page) => $page
            ->where('sku.stock_by_shop', [])
            ->where('sku.serialized_units.0.imei', '356938035601009')
            ->where('sku.serialized_units.0.shop_id', $shop->id)
            ->where('sku.serialized_units.0.status', 'available'));
    }

    public function test_a_shop_filtered_product_list_includes_a_serialized_sku_with_a_unit_at_that_shop(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $shop = ShopRecord::factory()->create();
        $otherShop = ShopRecord::factory()->create();
        $sku = SkuRecord::factory()->create(['is_serialized' => true, 'sku_code' => 'SER-VIS-001']);
        InventoryItemRecord::factory()->create(['sku_id' => $sku->id, 'current_shop_id' => $shop->id]);

        $response = $this->actingAs($owner, 'staff')->get("/admin/inventory/products?shop_id={$shop->id}");
        $response->assertInertia(fn ($page) => $page->has('products.data', 1)->where('products.data.0.sku_code', 'SER-VIS-001'));

        $emptyResponse = $this->actingAs($owner, 'staff')->get("/admin/inventory/products?shop_id={$otherShop->id}");
        $emptyResponse->assertInertia(fn ($page) => $page->has('products.data', 0));
    }
}
