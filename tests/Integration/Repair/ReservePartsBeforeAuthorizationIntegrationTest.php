<?php

declare(strict_types=1);

namespace Tests\Integration\Repair;

use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryStockLevelRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Repair\Application\Commands\ReserveRepairPartsCommand;
use Domain\Repair\Application\Handlers\ReserveRepairPartsHandler;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Parts are set aside during functional test & diagnosis and while the quote is open, so the manager can price them in. */
class ReservePartsBeforeAuthorizationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_parts_can_be_reserved_during_diagnosis_and_while_the_quote_is_open(): void
    {
        $shop = ShopRecord::factory()->create();
        $staff = StaffRecord::factory()->create();
        $sku = SkuRecord::factory()->nonSerialized()->create();
        InventoryStockLevelRecord::factory()->create(['sku_id' => $sku->id, 'shop_id' => $shop->id, 'on_hand' => 5, 'reserved' => 0]);

        $diagnosing = RepairJobRecord::factory()->create(['shop_id' => $shop->id, 'repair_status' => 'diagnosing']);
        $quoting = RepairJobRecord::factory()->create(['shop_id' => $shop->id, 'repair_status' => 'awaiting_authorization']);

        app(ReserveRepairPartsHandler::class)->handle(new ReserveRepairPartsCommand($diagnosing->id, $sku->id, 1, $staff->id));
        app(ReserveRepairPartsHandler::class)->handle(new ReserveRepairPartsCommand($quoting->id, $sku->id, 1, $staff->id));

        $this->assertDatabaseHas('repair_parts_reservations', ['repair_job_id' => $diagnosing->id, 'status' => 'reserved']);
        $this->assertDatabaseHas('repair_parts_reservations', ['repair_job_id' => $quoting->id, 'status' => 'reserved']);
        $this->assertDatabaseHas('inventory_stock_levels', ['sku_id' => $sku->id, 'shop_id' => $shop->id, 'reserved' => 2]);
    }
}
