<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Inventory\Application\Commands\ConsumeInventoryCommand;
use Domain\Inventory\Domain\Repositories\InventoryItemRepository;
use Domain\Inventory\Domain\Repositories\InventoryStockLevelRepository;
use Domain\Inventory\Domain\Repositories\SkuRepository;
use Domain\Inventory\Domain\ValueObjects\SkuId;
use Domain\Shared\Domain\ValueObjects\ShopId;

final class ConsumeInventoryHandler
{
    public function __construct(
        private readonly SkuRepository $skus,
        private readonly InventoryItemRepository $items,
        private readonly InventoryStockLevelRepository $stockLevels,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(ConsumeInventoryCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $skuId = new SkuId($command->skuId);
            $shopId = new ShopId($command->shopId);
            $sku = $this->skus->get($skuId);

            if ($sku->isSerialized()) {
                $ids = $this->items->findReservedBySource($skuId, $command->sourceType, $command->sourceId, $command->quantity);
                $locked = $this->items->lockByIds($ids);

                foreach ($locked as $item) {
                    $item->consume();
                    $this->items->save($item);
                }
            } else {
                $level = $this->stockLevels->lockForUpdate($skuId, $shopId);
                $level->consume($command->quantity);
                $this->stockLevels->save($level);
            }

            $this->audit->record(
                module: 'Inventory',
                eventType: 'InventoryConsumed',
                actorStaffId: null,
                actorRoleSnapshot: null,
                subjectType: 'sku',
                subjectId: $skuId->value,
                beforeState: null,
                afterState: [
                    'shop_id' => $shopId->value,
                    'quantity' => $command->quantity,
                    'source_type' => $command->sourceType,
                    'source_id' => $command->sourceId,
                ],
            );
        });
    }
}
