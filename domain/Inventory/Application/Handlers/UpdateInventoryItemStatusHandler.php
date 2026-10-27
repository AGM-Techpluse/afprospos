<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Inventory\Application\Commands\UpdateInventoryItemStatusCommand;
use Domain\Inventory\Domain\Exceptions\InvalidInventoryItemStatusRequested;
use Domain\Inventory\Domain\Repositories\InventoryItemRepository;
use Domain\Inventory\Domain\ValueObjects\InventoryItemId;
use Domain\Shared\Domain\ValueObjects\StaffId;

/** Marks one specific serialized unit damaged, or restores a previously-damaged unit to available — see InventoryItem::markDamaged()/restoreToAvailable(). */
final class UpdateInventoryItemStatusHandler
{
    public function __construct(
        private readonly InventoryItemRepository $items,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(UpdateInventoryItemStatusCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $itemId = new InventoryItemId($command->inventoryItemId);
            $item = $this->items->get($itemId);
            $before = $item->status();

            if ($command->toStatus === 'damaged') {
                $item->markDamaged();
            } elseif ($command->toStatus === 'available') {
                $item->restoreToAvailable();
            } else {
                throw InvalidInventoryItemStatusRequested::forStatus($command->toStatus);
            }

            $this->items->save($item);

            $this->audit->record(
                module: 'Inventory',
                eventType: $command->toStatus === 'damaged' ? 'InventoryItemMarkedDamaged' : 'InventoryItemRestoredToAvailable',
                actorStaffId: new StaffId($command->updatedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'inventory_item',
                subjectId: $itemId->value,
                beforeState: ['status' => $before],
                afterState: ['status' => $item->status()],
                context: $command->note !== null ? ['note' => $command->note] : null,
            );
        });
    }
}
