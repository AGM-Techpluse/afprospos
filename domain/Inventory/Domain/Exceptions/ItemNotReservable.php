<?php

declare(strict_types=1);

namespace Domain\Inventory\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

/**
 * DBDD §5.3/§27.2: only an item that is `available`, at the requested
 * shop, and not already reserved may become reserved. An IMEI can
 * never be reserved twice (Phase 3 exit criterion).
 */
final class ItemNotReservable extends DomainException
{
    public static function forItem(int $itemId): self
    {
        return new self("Inventory item [{$itemId}] is not available for reservation.");
    }
}
