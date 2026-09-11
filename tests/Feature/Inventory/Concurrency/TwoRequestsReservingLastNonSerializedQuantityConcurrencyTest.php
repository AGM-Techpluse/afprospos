<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory\Concurrency;

use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryStockLevelRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Symfony\Component\Process\Process;
use Tests\Feature\Inventory\Concurrency\Support\UsesConcurrencyDatabase;
use Tests\TestCase;

/**
 * Phase 3 exit criterion: "the last item/quantity succeeds for only
 * one genuinely concurrent request." Two real OS processes, each with
 * their own DB connection, race for the single remaining unit of
 * non-serialized stock — DBDD §25.1's SELECT ... FOR UPDATE is what
 * makes exactly one of them win.
 */
class TwoRequestsReservingLastNonSerializedQuantityConcurrencyTest extends TestCase
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

    public function test_only_one_of_two_simultaneous_requests_reserves_the_last_quantity(): void
    {
        $shop = ShopRecord::factory()->create();
        $sku = SkuRecord::factory()->nonSerialized()->create();
        InventoryStockLevelRecord::factory()->create([
            'sku_id' => $sku->id,
            'shop_id' => $shop->id,
            'on_hand' => 1,
            'reserved' => 0,
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

        $level = InventoryStockLevelRecord::query()
            ->where('sku_id', $sku->id)
            ->where('shop_id', $shop->id)
            ->firstOrFail();

        $this->assertSame(1, $level->on_hand, 'on_hand must be untouched by a reservation (BLD §2.1: reservation is not a stock-out).');
        $this->assertSame(1, $level->reserved, 'exactly one unit must end up reserved — never zero, never two.');
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
