<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Inventory\Application\Commands\ReceiveInventoryTransferCommand;
use Domain\Inventory\Domain\Repositories\InventoryItemRepository;
use Domain\Inventory\Domain\Repositories\InventoryStockLevelRepository;
use Domain\Inventory\Domain\Repositories\InventoryTransferRepository;
use Domain\Inventory\Domain\ValueObjects\InventoryItemId;
use Domain\Inventory\Domain\ValueObjects\InventoryTransferId;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class ReceiveInventoryTransferHandler
{
    public function __construct(
        private readonly InventoryTransferRepository $transfers,
        private readonly InventoryItemRepository $items,
        private readonly InventoryStockLevelRepository $stockLevels,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(ReceiveInventoryTransferCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $transfer = $this->transfers->get(new InventoryTransferId($command->transferId));
            $receivedBy = new StaffId($command->receivedByStaffId);

            $transfer->receive($receivedBy);
            $this->transfers->save($transfer);

            if ($transfer->inventoryItemId() !== null) {
                $item = $this->items->get(new InventoryItemId($transfer->inventoryItemId()));
                $item->completeTransfer($transfer->toShopId());
                $this->items->save($item);
            } else {
                $level = $this->stockLevels->lockForUpdate($transfer->skuId(), $transfer->toShopId());
                $level->receive($transfer->quantity() ?? 0);
                $this->stockLevels->save($level);
            }

            $this->audit->record(
                module: 'Inventory',
                eventType: 'InventoryTransferReceived',
                actorStaffId: $receivedBy,
                actorRoleSnapshot: null,
                subjectType: 'inventory_transfer',
                subjectId: $transfer->id()->value,
                beforeState: null,
                afterState: ['status' => 'completed'],
            );
        });
    }
}
