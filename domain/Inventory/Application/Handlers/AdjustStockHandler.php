<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Inventory\Application\Commands\AdjustStockCommand;
use Domain\Inventory\Domain\Repositories\InventoryStockLevelRepository;
use Domain\Inventory\Domain\ValueObjects\SkuId;
use Domain\Shared\Domain\ValueObjects\ShopId;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class AdjustStockHandler
{
    public function __construct(
        private readonly InventoryStockLevelRepository $stockLevels,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(AdjustStockCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $skuId = new SkuId($command->skuId);
            $shopId = new ShopId($command->shopId);

            $level = $this->stockLevels->lockForUpdate($skuId, $shopId);
            $before = $level->onHand();

            $level->adjust($command->delta);
            $this->stockLevels->save($level);

            $this->audit->record(
                module: 'Inventory',
                eventType: 'StockAdjusted',
                actorStaffId: new StaffId($command->adjustedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'stock_level',
                subjectId: $level->id()->value,
                beforeState: ['on_hand' => $before],
                afterState: ['on_hand' => $level->onHand()],
                context: ['reason' => $command->reason, 'delta' => $command->delta],
            );
        });
    }
}
