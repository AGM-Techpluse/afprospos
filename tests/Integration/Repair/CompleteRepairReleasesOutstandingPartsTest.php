<?php

declare(strict_types=1);

namespace Tests\Integration\Repair;

use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryStockLevelRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Repair\Application\Commands\CompleteRepairCommand;
use Domain\Repair\Application\Commands\InstallRepairPartCommand;
use Domain\Repair\Application\Commands\ReserveRepairPartsCommand;
use Domain\Repair\Application\Handlers\CompleteRepairHandler;
use Domain\Repair\Application\Handlers\InstallRepairPartHandler;
use Domain\Repair\Application\Handlers\ReserveRepairPartsHandler;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairPartReservationRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A part reserved but never marked installed was never actually used —
 * completing the repair must release it back to Inventory, not leave
 * that stock silently locked forever.
 */
class CompleteRepairReleasesOutstandingPartsTest extends TestCase
{
    use RefreshDatabase;

    public function test_completing_a_repair_releases_a_still_reserved_part_but_leaves_an_installed_one_alone(): void
    {
        $technician = StaffRecord::factory()->create();
        $shop = ShopRecord::factory()->create();
        $job = RepairJobRecord::factory()->create(['shop_id' => $shop->id, 'technician_staff_id' => $technician->id, 'repair_status' => 'in_progress']);

        $neverInstalledSku = SkuRecord::factory()->nonSerialized()->create();
        InventoryStockLevelRecord::factory()->create(['sku_id' => $neverInstalledSku->id, 'shop_id' => $shop->id, 'on_hand' => 5, 'reserved' => 0]);
        $installedSku = SkuRecord::factory()->nonSerialized()->create();
        InventoryStockLevelRecord::factory()->create(['sku_id' => $installedSku->id, 'shop_id' => $shop->id, 'on_hand' => 5, 'reserved' => 0]);

        app(ReserveRepairPartsHandler::class)->handle(new ReserveRepairPartsCommand(
            repairJobId: $job->id,
            skuId: $neverInstalledSku->id,
            quantity: 1,
            reservedByStaffId: $technician->id,
        ));
        app(ReserveRepairPartsHandler::class)->handle(new ReserveRepairPartsCommand(
            repairJobId: $job->id,
            skuId: $installedSku->id,
            quantity: 1,
            reservedByStaffId: $technician->id,
        ));

        $installedReservationId = RepairPartReservationRecord::query()->where('sku_id', $installedSku->id)->value('id');
        app(InstallRepairPartHandler::class)->handle(new InstallRepairPartCommand($installedReservationId));

        app(CompleteRepairHandler::class)->handle(new CompleteRepairCommand(
            repairJobId: $job->id,
            resolutionNotes: null,
            fixedFaults: [],
            completedByStaffId: $technician->id,
        ));

        // The never-installed reservation is released, and its stock hold is freed.
        $this->assertDatabaseHas('repair_parts_reservations', ['sku_id' => $neverInstalledSku->id, 'status' => 'released']);
        $this->assertDatabaseHas('inventory_stock_levels', ['sku_id' => $neverInstalledSku->id, 'shop_id' => $shop->id, 'on_hand' => 5, 'reserved' => 0]);

        // The installed one is untouched.
        $this->assertDatabaseHas('repair_parts_reservations', ['sku_id' => $installedSku->id, 'status' => 'installed']);
        $this->assertDatabaseHas('inventory_stock_levels', ['sku_id' => $installedSku->id, 'shop_id' => $shop->id, 'on_hand' => 4, 'reserved' => 0]);
    }
}
