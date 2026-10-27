<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Inventory\Application\Commands\ReconcileStockCountsCommand;
use Domain\Inventory\Domain\Repositories\InventoryStockLevelRepository;
use Domain\Inventory\Domain\ValueObjects\SkuId;
use Domain\Shared\Domain\ValueObjects\ShopId;
use Domain\Shared\Domain\ValueObjects\StaffId;
use Throwable;

/**
 * A physical stock count, row by row, against each product's recorded
 * on-hand quantity — the delta is always computed server-side from the
 * current on-hand at lock time, never trusted from the client, so a
 * stale page (stock moved between load and submit) can't silently apply
 * the wrong adjustment. One row's failure (e.g. the physical count is
 * below what's currently reserved) doesn't lose the rest of the batch.
 */
final class ReconcileStockCountsHandler
{
    public function __construct(
        private readonly InventoryStockLevelRepository $stockLevels,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    /** @return array{succeeded: int, failures: array<int, string>} */
    public function handle(ReconcileStockCountsCommand $command): array
    {
        $succeeded = 0;
        $failures = [];

        foreach ($command->counts as $count) {
            try {
                $this->atomic->run(function () use ($count, $command, &$succeeded): void {
                    $skuId = new SkuId($count['sku_id']);
                    $shopId = new ShopId($count['shop_id']);

                    $level = $this->stockLevels->lockForUpdate($skuId, $shopId);
                    $systemCount = $level->onHand();
                    $delta = $count['physical_count'] - $systemCount;

                    if ($delta === 0) {
                        return;
                    }

                    $level->adjust($delta);
                    $this->stockLevels->save($level);

                    $this->audit->record(
                        module: 'Inventory',
                        eventType: 'StockReconciled',
                        actorStaffId: new StaffId($command->reconciledByStaffId),
                        actorRoleSnapshot: null,
                        subjectType: 'stock_level',
                        subjectId: $level->id()->value,
                        beforeState: ['on_hand' => $systemCount],
                        afterState: ['on_hand' => $level->onHand()],
                        context: [
                            'reason_code' => $count['reason_code'],
                            'note' => $count['note'],
                            'system_count' => $systemCount,
                            'physical_count' => $count['physical_count'],
                            'delta' => $delta,
                        ],
                    );

                    $succeeded++;
                });
            } catch (Throwable $exception) {
                $failures[] = "SKU #{$count['sku_id']}: {$exception->getMessage()}";
            }
        }

        return ['succeeded' => $succeeded, 'failures' => $failures];
    }
}
