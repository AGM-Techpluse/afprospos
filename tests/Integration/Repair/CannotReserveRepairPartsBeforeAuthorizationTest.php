<?php

declare(strict_types=1);

namespace Tests\Integration\Repair;

use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryStockLevelRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Repair\Application\Commands\CreateRepairJobCommand;
use Domain\Repair\Application\Commands\ReserveRepairPartsCommand;
use Domain\Repair\Application\Handlers\CreateRepairJobHandler;
use Domain\Repair\Application\Handlers\ReserveRepairPartsHandler;
use Domain\Repair\Domain\Exceptions\InvalidRepairTransition;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Parts are set aside (reserved) from diagnosis onwards, so the manager can price them into the quote.
 * Reserving never consumes stock: that only happens when the part is fitted during the repair.
 */
class CannotReserveRepairPartsBeforeAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_parts_can_be_set_aside_while_diagnosing_and_stock_is_held_not_consumed(): void
    {
        $shop = ShopRecord::factory()->create();
        $staff = StaffRecord::factory()->create();
        $sku = SkuRecord::factory()->nonSerialized()->create();
        InventoryStockLevelRecord::factory()->create(['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 5, 'reserved' => 0]);

        $job = RepairJobRecord::factory()->create(['shop_id' => $shop->id, 'repair_status' => 'diagnosing']);

        app(ReserveRepairPartsHandler::class)->handle(new ReserveRepairPartsCommand(
            repairJobId: $job->id,
            skuId: $sku->id,
            quantity: 1,
            reservedByStaffId: $staff->id,
        ));

        $this->assertDatabaseHas('inventory_stock_levels', ['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 5, 'reserved' => 1]);
    }

    public function test_reserving_a_part_snapshots_its_current_selling_price_and_a_later_price_change_does_not_affect_it(): void
    {
        $shop = ShopRecord::factory()->create();
        $staff = StaffRecord::factory()->create();
        $sku = SkuRecord::factory()->nonSerialized()->create(['selling_price_minor' => 1_500_00]);
        InventoryStockLevelRecord::factory()->create(['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 5, 'reserved' => 0]);

        $job = RepairJobRecord::factory()->create(['shop_id' => $shop->id, 'repair_status' => 'diagnosing']);

        app(ReserveRepairPartsHandler::class)->handle(new ReserveRepairPartsCommand(
            repairJobId: $job->id,
            skuId: $sku->id,
            quantity: 2,
            reservedByStaffId: $staff->id,
        ));

        $this->assertDatabaseHas('repair_parts_reservations', [
            'repair_job_id' => $job->id,
            'sku_id' => $sku->id,
            'unit_price_minor' => 1_500_00,
        ]);

        $sku->update(['selling_price_minor' => 9_999_00]);

        $this->assertDatabaseHas('repair_parts_reservations', [
            'repair_job_id' => $job->id,
            'sku_id' => $sku->id,
            'unit_price_minor' => 1_500_00,
        ]);
    }

    public function test_parts_cannot_be_set_aside_before_the_fault_is_diagnosed(): void
    {
        $shop = ShopRecord::factory()->create();
        $staff = StaffRecord::factory()->create();
        $sku = SkuRecord::factory()->nonSerialized()->create();
        InventoryStockLevelRecord::factory()->create(['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 5, 'reserved' => 0]);

        $job = RepairJobRecord::factory()->create(['shop_id' => $shop->id, 'repair_status' => 'received']);

        $this->expectException(InvalidRepairTransition::class);

        app(ReserveRepairPartsHandler::class)->handle(new ReserveRepairPartsCommand(
            repairJobId: $job->id,
            skuId: $sku->id,
            quantity: 1,
            reservedByStaffId: $staff->id,
        ));
    }

    public function test_a_walk_in_repair_can_be_taken_without_a_customer(): void
    {
        $shop = ShopRecord::factory()->create();
        $staff = StaffRecord::factory()->create();

        $id = app(CreateRepairJobHandler::class)->handle(new CreateRepairJobCommand(
            shopId: $shop->id,
            customerId: null,
            deviceMake: 'Tecno',
            deviceModel: 'Spark 10',
            reportedIssue: 'No display',
            deviceImeiSerial: null,
            deviceLockType: 'none',
            deviceLockValue: null,
            problemTagIds: [],
            labourChargeMinor: null,
            createdByStaffId: $staff->id,
        ));

        $this->assertDatabaseHas('repair_jobs', ['id' => $id, 'customer_id' => null, 'repair_status' => 'received']);
    }
}
