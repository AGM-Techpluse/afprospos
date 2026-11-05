<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Inventory\Application\Commands\UpdateInventoryItemConditionCommand;
use Domain\Inventory\Domain\Exceptions\UnknownMasterEntry;
use Domain\Inventory\Domain\Repositories\InventoryItemRepository;
use Domain\Inventory\Domain\Repositories\InventoryMasterRepository;
use Domain\Inventory\Domain\ValueObjects\InventoryItemId;
use Domain\Shared\Domain\ValueObjects\StaffId;

/** Corrects a serialized unit's condition grading — see InventoryItem::changeCondition(). */
final class UpdateInventoryItemConditionHandler
{
    public function __construct(
        private readonly InventoryItemRepository $items,
        private readonly InventoryMasterRepository $masters,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(UpdateInventoryItemConditionCommand $command): void
    {
        if (! $this->masters->exists('condition', $command->condition)) {
            throw UnknownMasterEntry::forName('condition', $command->condition);
        }

        $this->atomic->run(function () use ($command): void {
            $itemId = new InventoryItemId($command->inventoryItemId);
            $item = $this->items->get($itemId);
            $before = $item->condition();

            $item->changeCondition($command->condition);
            $this->items->save($item);

            $this->audit->record(
                module: 'Inventory',
                eventType: 'InventoryItemConditionChanged',
                actorStaffId: new StaffId($command->updatedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'inventory_item',
                subjectId: $itemId->value,
                beforeState: ['condition' => $before],
                afterState: ['condition' => $item->condition()],
                context: null,
            );
        });
    }
}
