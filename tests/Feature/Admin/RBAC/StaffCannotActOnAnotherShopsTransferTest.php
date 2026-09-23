<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\RBAC;

use Database\Seeders\RolePermissionSeeder;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryTransferRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** BRD RBAC-05: a transfer touches two shops — being granted only one end still blocks the action. */
class StaffCannotActOnAnotherShopsTransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_staff_member_granted_only_the_from_shop_cannot_receive_a_transfer_into_an_ungranted_shop(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $fromShop = ShopRecord::factory()->create();
        $toShop = ShopRecord::factory()->create();

        $staff = StaffRecord::factory()->create();
        $staff->assignRole('Product Staff');
        $this->grantShopAccess($staff, $fromShop->id);

        $transfer = InventoryTransferRecord::factory()->create([
            'from_shop_id' => $fromShop->id,
            'to_shop_id' => $toShop->id,
            'status' => 'in_transit',
        ]);

        $response = $this->actingAs($staff, 'staff')->post("/admin/inventory/transfers/{$transfer->id}/receive");

        $response->assertForbidden();
        $this->assertDatabaseHas('inventory_transfers', ['id' => $transfer->id, 'status' => 'in_transit']);
    }
}
