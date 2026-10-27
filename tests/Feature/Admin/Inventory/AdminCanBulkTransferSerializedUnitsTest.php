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

class AdminCanBulkTransferSerializedUnitsTest extends TestCase
{
    use RefreshDatabase;

    public function test_several_selected_units_transfer_together_atomically(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $fromShop = ShopRecord::factory()->create();
        $toShop = ShopRecord::factory()->create();

        $sku = SkuRecord::factory()->create();
        $unitA = InventoryItemRecord::factory()->create(['sku_id' => $sku->id, 'current_shop_id' => $fromShop->id]);
        $unitB = InventoryItemRecord::factory()->create(['sku_id' => $sku->id, 'current_shop_id' => $fromShop->id]);
        $unitC = InventoryItemRecord::factory()->create(['sku_id' => $sku->id, 'current_shop_id' => $fromShop->id]);

        $response = $this->actingAs($owner, 'staff')->post('/admin/inventory/transfers/bulk-serialized', [
            'units' => [
                ['sku_id' => $sku->id, 'inventory_item_id' => $unitA->id],
                ['sku_id' => $sku->id, 'inventory_item_id' => $unitB->id],
                ['sku_id' => $sku->id, 'inventory_item_id' => $unitC->id],
            ],
            'from_shop_id' => $fromShop->id,
            'to_shop_id' => $toShop->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('inventory_items', ['id' => $unitA->id, 'status' => 'transferring']);
        $this->assertDatabaseHas('inventory_items', ['id' => $unitB->id, 'status' => 'transferring']);
        $this->assertDatabaseHas('inventory_items', ['id' => $unitC->id, 'status' => 'transferring']);
        $this->assertDatabaseCount('inventory_transfers', 3);
    }

    public function test_one_unit_already_mid_transfer_rolls_back_the_whole_batch(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $fromShop = ShopRecord::factory()->create();
        $toShop = ShopRecord::factory()->create();

        $sku = SkuRecord::factory()->create();
        $unitA = InventoryItemRecord::factory()->create(['sku_id' => $sku->id, 'current_shop_id' => $fromShop->id]);
        // Already transferring — beginTransfer() will throw for this one.
        $unitB = InventoryItemRecord::factory()->create([
            'sku_id' => $sku->id,
            'current_shop_id' => $fromShop->id,
            'status' => 'transferring',
        ]);

        $response = $this->actingAs($owner, 'staff')->post('/admin/inventory/transfers/bulk-serialized', [
            'units' => [
                ['sku_id' => $sku->id, 'inventory_item_id' => $unitA->id],
                ['sku_id' => $sku->id, 'inventory_item_id' => $unitB->id],
            ],
            'from_shop_id' => $fromShop->id,
            'to_shop_id' => $toShop->id,
        ]);

        $response->assertSessionHasErrors();
        // The whole batch rolled back — unitA must NOT have been transferred either.
        $this->assertDatabaseHas('inventory_items', ['id' => $unitA->id, 'status' => 'available']);
        $this->assertDatabaseCount('inventory_transfers', 0);
    }

    public function test_blocks_a_staff_member_without_inventory_edit(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $fromShop = ShopRecord::factory()->create();
        $toShop = ShopRecord::factory()->create();

        $sku = SkuRecord::factory()->create();
        $unit = InventoryItemRecord::factory()->create(['sku_id' => $sku->id, 'current_shop_id' => $fromShop->id]);

        $response = $this->actingAs($cashier, 'staff')->post('/admin/inventory/transfers/bulk-serialized', [
            'units' => [['sku_id' => $sku->id, 'inventory_item_id' => $unit->id]],
            'from_shop_id' => $fromShop->id,
            'to_shop_id' => $toShop->id,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('inventory_items', ['id' => $unit->id, 'status' => 'available']);
    }
}
