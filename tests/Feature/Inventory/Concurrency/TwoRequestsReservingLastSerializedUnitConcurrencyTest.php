<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory\Concurrency;

use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryItemRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Symfony\Component\Process\Process;
use Tests\Support\UsesConcurrencyDatabase;
use Tests\TestCase;

/**
 * Phase 3 exit criterion: "an IMEI cannot be reserved twice." Two real
 * OS processes race to reserve the single available serialized unit —
 * DBDD §5.3/§25.2's SELECT ... FOR UPDATE on the specific inventory_items
 * row is what makes exactly one of them win.
 */
class TwoRequestsReservingLastSerializedUnitConcurrencyTest extends TestCase
{
    use UsesConcurrencyDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpConcurrencyDatabase();
    }

    protected function tearDown(): void
    {
        $this->tearDownConcurrencyDatabase();
        parent::tearDown();
    }

    public function test_only_one_of_two_simultaneous_requests_reserves_the_last_serialized_unit(): void
    {
        $shop = ShopRecord::factory()->create();
        $sku = SkuRecord::factory()->create();
        $item = InventoryItemRecord::factory()->create([
            'sku_id' => $sku->id,
            'current_shop_id' => $shop->id,
            'status' => 'available',
        ]);

        $processA = $this->buildProcess($sku->id, $shop->id, 101, holdMs: 400);
        $processB = $this->buildProcess($sku->id, $shop->id, 102, holdMs: 0);

        $processA->start();
        usleep(50_000);
        $processB->start();

        $processA->wait();
        $processB->wait();

        $resultA = json_decode($processA->getOutput(), true);
        $resultB = json_decode($processB->getOutput(), true);

        $successCount = (int) ($resultA['success'] ?? false) + (int) ($resultB['success'] ?? false);

        $this->assertSame(
            1,
            $successCount,
            "Expected exactly one of the two concurrent requests to succeed.\n".
            'A: '.$processA->getOutput().' '.$processA->getErrorOutput()."\n".
            'B: '.$processB->getOutput().' '.$processB->getErrorOutput(),
        );

        $fresh = InventoryItemRecord::query()->findOrFail($item->id);
        $this->assertSame('reserved', $fresh->status);
        $this->assertNotNull($fresh->reserved_by_id, 'the winning source must be recorded — an IMEI reserved by nobody is a broken state.');
    }

    private function buildProcess(int $skuId, int $shopId, int $sourceId, int $holdMs): Process
    {
        return new Process([
            PHP_BINARY,
            __DIR__.'/Support/attempt_reservation.php',
            "--sku={$skuId}",
            "--shop={$shopId}",
            '--quantity=1',
            "--source-id={$sourceId}",
            '--source-type=checkout',
            "--hold-ms={$holdMs}",
        ], timeout: 15);
    }
}
