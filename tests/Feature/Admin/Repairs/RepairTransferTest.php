<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Repairs;

use Database\Seeders\RolePermissionSeeder;
use Domain\Collection\Infrastructure\Persistence\Eloquent\CollectionCaseRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryStockLevelRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairPartReservationRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RepairTransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_transfer_an_open_repair_to_another_accessible_shop(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $sourceShop = ShopRecord::factory()->create();
        $targetShop = ShopRecord::factory()->create();
        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $this->grantShopAccess($cashier, $sourceShop->id);
        $this->grantShopAccess($cashier, $targetShop->id);

        $repair = RepairJobRecord::factory()->create([
            'shop_id' => $sourceShop->id,
            'technician_staff_id' => StaffRecord::factory()->create()->id,
            'repair_status' => 'failed_requires_resolution',
        ]);

        $response = $this->actingAs($cashier, 'staff')->post("/admin/repairs/{$repair->id}/transfer", [
            'target_shop_id' => $targetShop->id,
            'reason' => 'Needs a specialist technician',
        ]);

        $response->assertRedirect(route('admin.repairs.show', ['repair' => $repair->id]));
        $this->assertDatabaseHas('repair_jobs', [
            'id' => $repair->id,
            'shop_id' => $targetShop->id,
            'technician_staff_id' => null,
            'repair_status' => 'failed_requires_resolution',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'Repair',
            'event_type' => 'RepairTransferred',
            'subject_type' => 'repair_job',
            'subject_id' => $repair->id,
        ]);
    }

    public function test_repair_transfer_requires_access_to_the_target_shop(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $sourceShop = ShopRecord::factory()->create();
        $targetShop = ShopRecord::factory()->create();
        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $this->grantShopAccess($cashier, $sourceShop->id);

        $repair = RepairJobRecord::factory()->create(['shop_id' => $sourceShop->id]);

        $response = $this->actingAs($cashier, 'staff')->post("/admin/repairs/{$repair->id}/transfer", [
            'target_shop_id' => $targetShop->id,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('repair_jobs', ['id' => $repair->id, 'shop_id' => $sourceShop->id]);
    }

    public function test_repair_transfer_releases_reserved_parts_when_requested(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $sourceShop = ShopRecord::factory()->create();
        $targetShop = ShopRecord::factory()->create();
        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $this->grantShopAccess($cashier, $sourceShop->id);
        $this->grantShopAccess($cashier, $targetShop->id);

        $repair = RepairJobRecord::factory()->create(['shop_id' => $sourceShop->id, 'repair_status' => 'diagnosing']);
        $sku = SkuRecord::factory()->nonSerialized()->create();
        InventoryStockLevelRecord::factory()->create([
            'sku_id' => $sku->id,
            'shop_id' => $sourceShop->id,
            'on_hand' => 10,
            'reserved' => 2,
        ]);
        RepairPartReservationRecord::factory()->create([
            'repair_job_id' => $repair->id,
            'sku_id' => $sku->id,
            'quantity' => 2,
            'status' => 'reserved',
        ]);

        $response = $this->actingAs($cashier, 'staff')->post("/admin/repairs/{$repair->id}/transfer", [
            'target_shop_id' => $targetShop->id,
            'release_reserved_parts' => '1',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('repair_jobs', ['id' => $repair->id, 'shop_id' => $targetShop->id]);
        $this->assertDatabaseHas('repair_parts_reservations', ['repair_job_id' => $repair->id, 'status' => 'released']);
        $this->assertDatabaseHas('inventory_stock_levels', [
            'sku_id' => $sku->id,
            'shop_id' => $sourceShop->id,
            'reserved' => 0,
        ]);
    }

    public function test_repair_transfer_is_blocked_after_collection_case_is_opened(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $sourceShop = ShopRecord::factory()->create();
        $targetShop = ShopRecord::factory()->create();
        $manager = StaffRecord::factory()->create();
        $manager->assignRole('Manager');
        $this->grantShopAccess($manager, $sourceShop->id);
        $this->grantShopAccess($manager, $targetShop->id);

        $repair = RepairJobRecord::factory()->create(['shop_id' => $sourceShop->id, 'repair_status' => 'handover']);
        CollectionCaseRecord::factory()->create([
            'source_type' => 'repair_job',
            'source_id' => $repair->id,
            'shop_id' => $sourceShop->id,
            'originating_shop_id' => $sourceShop->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($manager, 'staff')->post("/admin/repairs/{$repair->id}/transfer", [
            'target_shop_id' => $targetShop->id,
        ]);

        $response->assertSessionHas('flash.kind', 'danger');
        $this->assertDatabaseHas('repair_jobs', ['id' => $repair->id, 'shop_id' => $sourceShop->id]);
    }
}
