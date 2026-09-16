<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Inventory;

use Database\Seeders\RolePermissionSeeder;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryItemRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryStockLevelRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The product detail page's headline stat row (UI/UX 14C.8) sums stock business-wide -- from inventory_stock_levels for a bulk SKU, from counting inventory_items by status for a serialized one. */
class ProductAvailableCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_serialized_products_available_count_sums_available_units_across_shops(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $shopA = ShopRecord::factory()->create();
        $shopB = ShopRecord::factory()->create();
        $sku = SkuRecord::factory()->create(['is_serialized' => true]);
        InventoryItemRecord::factory()->create(['sku_id' => $sku->id, 'current_shop_id' => $shopA->id, 'status' => 'available']);
        InventoryItemRecord::factory()->create(['sku_id' => $sku->id, 'current_shop_id' => $shopB->id, 'status' => 'available']);
        InventoryItemRecord::factory()->create(['sku_id' => $sku->id, 'current_shop_id' => $shopB->id, 'status' => 'reserved']);
        InventoryItemRecord::factory()->create(['sku_id' => $sku->id, 'current_shop_id' => $shopB->id, 'status' => 'sold']);

        $response = $this->actingAs($owner, 'staff')->get("/admin/inventory/products/{$sku->id}");

        // on_hand = available + reserved (sold doesn't count); available = on_hand - reserved.
        $response->assertInertia(fn ($page) => $page
            ->where('sku.on_hand', 3)
            ->where('sku.reserved', 1)
            ->where('sku.available', 2));
    }

    public function test_a_bulk_products_available_count_sums_stock_levels_across_shops(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $shopA = ShopRecord::factory()->create();
        $shopB = ShopRecord::factory()->create();
        $sku = SkuRecord::factory()->nonSerialized()->create();
        InventoryStockLevelRecord::factory()->create(['sku_id' => $sku->id, 'shop_id' => $shopA->id, 'on_hand' => 10, 'reserved' => 2]);
        InventoryStockLevelRecord::factory()->create(['sku_id' => $sku->id, 'shop_id' => $shopB->id, 'on_hand' => 5, 'reserved' => 1]);

        $response = $this->actingAs($owner, 'staff')->get("/admin/inventory/products/{$sku->id}");

        $response->assertInertia(fn ($page) => $page
            ->where('sku.on_hand', 15)
            ->where('sku.reserved', 3)
            ->where('sku.available', 12));
    }
}
