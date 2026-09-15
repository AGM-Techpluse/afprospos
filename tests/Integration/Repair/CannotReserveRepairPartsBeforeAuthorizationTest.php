<?php

declare(strict_types=1);

namespace Tests\Integration\Repair;

use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryStockLevelRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Repair\Application\Commands\ReserveRepairPartsCommand;
use Domain\Repair\Application\Handlers\ReserveRepairPartsHandler;
use Domain\Repair\Domain\Exceptions\InvalidRepairTransition;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Implementation Plan Phase 6 exit criterion: "parts are never silently consumed at selection" — reservation must never precede authorization. */
class CannotReserveRepairPartsBeforeAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_reserving_parts_before_authorization_is_rejected(): void
    {
        $shop = ShopRecord::factory()->create();
        $staff = StaffRecord::factory()->create();
        $sku = SkuRecord::factory()->nonSerialized()->create();
        InventoryStockLevelRecord::factory()->create(['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 5, 'reserved' => 0]);

        $job = RepairJobRecord::factory()->create(['shop_id' => $shop->id, 'repair_status' => 'diagnosing']);

        $this->expectException(InvalidRepairTransition::class);

        app(ReserveRepairPartsHandler::class)->handle(new ReserveRepairPartsCommand(
            repairJobId: $job->id,
            skuId: $sku->id,
            quantity: 1,
            reservedByStaffId: $staff->id,
        ));
    }

    public function test_reserving_parts_while_received_is_also_rejected(): void
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

        $this->assertDatabaseMissing('inventory_stock_levels', ['sku_id' => $sku->id, 'reserved' => 1]);
    }
}
