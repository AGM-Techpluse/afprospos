<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Inventory;

use Database\Seeders\RolePermissionSeeder;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryStockLevelRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCanAdjustAndReceiveStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_lets_an_admin_receive_additional_non_serialized_stock(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $shop = ShopRecord::factory()->create();
        $sku = SkuRecord::factory()->nonSerialized()->create();
        InventoryStockLevelRecord::factory()->create(['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 10, 'reserved' => 0]);

        $response = $this->actingAs($owner, 'staff')->post("/admin/inventory/products/{$sku->id}/receive", [
            'shop_id' => $shop->id,
            'condition' => 'new',
            'quantity' => 15,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('inventory_stock_levels', ['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 25]);
    }

    public function test_lets_an_admin_adjust_stock_with_a_reason(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $shop = ShopRecord::factory()->create();
        $sku = SkuRecord::factory()->nonSerialized()->create();
        InventoryStockLevelRecord::factory()->create(['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 10, 'reserved' => 0]);

        $response = $this->actingAs($owner, 'staff')->post("/admin/inventory/stock/{$sku->id}/{$shop->id}/adjust", [
            'delta' => -3,
            'reason' => 'Damaged in storage',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('inventory_stock_levels', ['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 7]);
        $this->assertDatabaseHas('audit_logs', ['module' => 'Inventory', 'event_type' => 'StockAdjusted']);
    }

    public function test_adjustment_requires_a_reason(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $shop = ShopRecord::factory()->create();
        $sku = SkuRecord::factory()->nonSerialized()->create();
        InventoryStockLevelRecord::factory()->create(['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 10, 'reserved' => 0]);

        $response = $this->actingAs($owner, 'staff')->post("/admin/inventory/stock/{$sku->id}/{$shop->id}/adjust", [
            'delta' => -3,
        ]);

        $response->assertSessionHasErrors('reason');
        $this->assertDatabaseHas('inventory_stock_levels', ['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 10]);
    }

    public function test_blocks_a_staff_member_without_inventory_edit_from_adjusting_stock(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier'); // has inventory.view but not inventory.edit
        $shop = ShopRecord::factory()->create();
        $sku = SkuRecord::factory()->nonSerialized()->create();
        InventoryStockLevelRecord::factory()->create(['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 10, 'reserved' => 0]);

        $response = $this->actingAs($cashier, 'staff')->post("/admin/inventory/stock/{$sku->id}/{$shop->id}/adjust", [
            'delta' => -3,
            'reason' => 'Should not be allowed',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('inventory_stock_levels', ['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 10]);
    }
}
