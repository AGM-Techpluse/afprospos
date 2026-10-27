<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Commands;

/** `toStatus` is either 'damaged' or 'available' — the only two transitions InventoryItem::markDamaged()/restoreToAvailable() support. */
final readonly class UpdateInventoryItemStatusCommand
{
    public function __construct(
        public int $inventoryItemId,
        public string $toStatus,
        public ?string $note,
        public int $updatedByStaffId,
    ) {}
}
