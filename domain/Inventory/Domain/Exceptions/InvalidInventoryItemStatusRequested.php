<?php

declare(strict_types=1);

namespace Domain\Inventory\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

/** UpdateInventoryItemStatusHandler only ever allows transitioning TO 'damaged' or 'available' — anything else is a request-shape bug, not a user-facing validation message. */
final class InvalidInventoryItemStatusRequested extends DomainException
{
    public static function forStatus(string $status): self
    {
        return new self("[{$status}] is not a status this action can set — only 'damaged' or 'available'.");
    }
}
