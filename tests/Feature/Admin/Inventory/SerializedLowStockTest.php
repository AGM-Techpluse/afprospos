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

/** A serialized SKU has no inventory_stock_levels row -- available count comes from counting inventory_items instead, so low-stock/out-of-stock detection needs its own branch for it (LowStockQuery::serializedSummaries). */
class SerializedLowStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_serialized_sku_running_low_appears_on_the_low_stock_page(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $shop = ShopRecord::factory()->create();
        $sku = SkuRecord::factory()->create(['is_serialized' => true, 'sku_code' => 'SER-LOW-001', 'low_stock_threshold' => 2]);
        InventoryItemRecord::factory()->create(['sku_id' => $sku->id, 'current_shop_id' => $shop->id, 'status' => 'available']);

        $response = $this->actingAs($owner, 'staff')->get('/admin/inventory/stock/low');

        $response->assertInertia(fn ($page) => $page
            ->has('items', 1)
            ->where('items.0.sku_code', 'SER-LOW-001')
            ->where('items.0.available', 1)
            ->where('items.0.is_serialized', true)
            ->where('items.0.stock_level_id', null));
    }

    public function test_a_serialized_sku_with_stock_above_threshold_does_not_appear(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $shop = ShopRecord::factory()->create();
        $sku = SkuRecord::factory()->create(['is_serialized' => true, 'low_stock_threshold' => 1]);
        InventoryItemRecord::factory()->count(3)->create(['sku_id' => $sku->id, 'current_shop_id' => $shop->id, 'status' => 'available']);

        $response = $this->actingAs($owner, 'staff')->get('/admin/inventory/stock/low');

        $response->assertInertia(fn ($page) => $page->has('items', 0));
    }

    public function test_dashboard_inventory_health_counts_include_serialized_skus(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $shop = ShopRecord::factory()->create();

        // Low but not zero.
        $lowSku = SkuRecord::factory()->create(['is_serialized' => true, 'low_stock_threshold' => 2]);
        InventoryItemRecord::factory()->create(['sku_id' => $lowSku->id, 'current_shop_id' => $shop->id, 'status' => 'available']);

        // Fully out of stock: the one unit is sold, none available.
        $outOfStockSku = SkuRecord::factory()->create(['is_serialized' => true, 'low_stock_threshold' => 2]);
        InventoryItemRecord::factory()->create(['sku_id' => $outOfStockSku->id, 'current_shop_id' => $shop->id, 'status' => 'sold']);

        $response = $this->actingAs($owner, 'staff')->get('/admin/dashboard');

        // Both SKUs count as "low stock" (0 <= 2 and 1 <= 2 both hold) --
        // an out-of-stock row is a low-stock row too, same double-count
        // parity the non-serialized branch already had.
        $response->assertInertia(fn ($page) => $page
            ->where('stats.inventoryHealth.low_stock_count', 2)
            ->where('stats.inventoryHealth.out_of_stock_count', 1));
    }
}
