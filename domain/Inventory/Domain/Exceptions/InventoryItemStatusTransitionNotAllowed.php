<?php

declare(strict_types=1);

namespace Domain\Inventory\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

/** Raised by InventoryItem::markDamaged()/restoreToAvailable() when the unit isn't in the required starting status. */
final class InventoryItemStatusTransitionNotAllowed extends DomainException
{
    public static function forItem(int $itemId, string $attemptedTransition): self
    {
        return new self("Inventory item [{$itemId}] cannot be {$attemptedTransition} in its current state.");
    }
}
