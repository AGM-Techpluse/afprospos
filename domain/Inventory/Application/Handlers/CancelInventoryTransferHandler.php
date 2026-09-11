<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Inventory\Application\Commands\CancelInventoryTransferCommand;
use Domain\Inventory\Domain\Repositories\InventoryItemRepository;
use Domain\Inventory\Domain\Repositories\InventoryStockLevelRepository;
use Domain\Inventory\Domain\Repositories\InventoryTransferRepository;
use Domain\Inventory\Domain\ValueObjects\InventoryItemId;
use Domain\Inventory\Domain\ValueObjects\InventoryTransferId;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class CancelInventoryTransferHandler
{
    public function __construct(
        private readonly InventoryTransferRepository $transfers,
        private readonly InventoryItemRepository $items,
        private readonly InventoryStockLevelRepository $stockLevels,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(CancelInventoryTransferCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $transfer = $this->transfers->get(new InventoryTransferId($command->transferId));

            $transfer->cancel();
            $this->transfers->save($transfer);

            if ($transfer->inventoryItemId() !== null) {
                $item = $this->items->get(new InventoryItemId($transfer->inventoryItemId()));
                $item->cancelTransfer();
                $this->items->save($item);
            } else {
                // Reverts the on_hand decrement transferOut() made at the source shop.
                $level = $this->stockLevels->lockForUpdate($transfer->skuId(), $transfer->fromShopId());
                $level->receive($transfer->quantity() ?? 0);
                $this->stockLevels->save($level);
            }

            $this->audit->record(
                module: 'Inventory',
                eventType: 'InventoryTransferCancelled',
                actorStaffId: new StaffId($command->cancelledByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'inventory_transfer',
                subjectId: $transfer->id()->value,
                beforeState: null,
                afterState: ['status' => 'cancelled'],
            );
        });
    }
}
