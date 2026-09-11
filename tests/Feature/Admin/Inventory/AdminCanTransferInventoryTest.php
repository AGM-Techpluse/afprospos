<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Inventory;

use Database\Seeders\RolePermissionSeeder;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryItemRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryTransferRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCanTransferInventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_lets_an_admin_initiate_and_receive_a_serialized_transfer(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $fromShop = ShopRecord::factory()->create();
        $toShop = ShopRecord::factory()->create();
        $item = InventoryItemRecord::factory()->create(['current_shop_id' => $fromShop->id]);

        $initiate = $this->actingAs($owner, 'staff')->post('/admin/inventory/transfers', [
            'sku_id' => $item->sku_id,
            'inventory_item_id' => $item->id,
            'from_shop_id' => $fromShop->id,
            'to_shop_id' => $toShop->id,
        ]);

        $initiate->assertRedirect();
        $this->assertDatabaseHas('inventory_transfers', ['inventory_item_id' => $item->id, 'status' => 'in_transit']);
        $this->assertDatabaseHas('inventory_items', ['id' => $item->id, 'status' => 'transferring']);

        $transferId = InventoryTransferRecord::query()
            ->where('inventory_item_id', $item->id)->firstOrFail()->id;

        $receive = $this->actingAs($owner, 'staff')->post("/admin/inventory/transfers/{$transferId}/receive");

        $receive->assertRedirect();
        $this->assertDatabaseHas('inventory_transfers', ['id' => $transferId, 'status' => 'completed']);
        $this->assertDatabaseHas('inventory_items', ['id' => $item->id, 'status' => 'available', 'current_shop_id' => $toShop->id]);
    }

    public function test_cancelling_a_transfer_returns_the_item_to_its_original_shop(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $fromShop = ShopRecord::factory()->create();
        $toShop = ShopRecord::factory()->create();
        $item = InventoryItemRecord::factory()->create(['current_shop_id' => $fromShop->id]);

        $this->actingAs($owner, 'staff')->post('/admin/inventory/transfers', [
            'sku_id' => $item->sku_id,
            'inventory_item_id' => $item->id,
            'from_shop_id' => $fromShop->id,
            'to_shop_id' => $toShop->id,
        ]);

        $transferId = InventoryTransferRecord::query()
            ->where('inventory_item_id', $item->id)->firstOrFail()->id;

        $cancel = $this->actingAs($owner, 'staff')->post("/admin/inventory/transfers/{$transferId}/cancel");

        $cancel->assertRedirect();
        $this->assertDatabaseHas('inventory_transfers', ['id' => $transferId, 'status' => 'cancelled']);
        $this->assertDatabaseHas('inventory_items', ['id' => $item->id, 'status' => 'available', 'current_shop_id' => $fromShop->id]);
    }

    public function test_a_reserved_item_cannot_be_transferred(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $fromShop = ShopRecord::factory()->create();
        $toShop = ShopRecord::factory()->create();
        $item = InventoryItemRecord::factory()->reserved()->create(['current_shop_id' => $fromShop->id]);

        $response = $this->actingAs($owner, 'staff')->post('/admin/inventory/transfers', [
            'sku_id' => $item->sku_id,
            'inventory_item_id' => $item->id,
            'from_shop_id' => $fromShop->id,
            'to_shop_id' => $toShop->id,
        ]);

        $response->assertSessionHasErrors();
        $this->assertDatabaseMissing('inventory_transfers', ['inventory_item_id' => $item->id]);
    }
}
