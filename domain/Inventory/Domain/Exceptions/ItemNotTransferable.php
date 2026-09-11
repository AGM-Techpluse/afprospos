<?php

declare(strict_types=1);

namespace Domain\Inventory\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

/**
 * INV-BR-14: a reserved or otherwise unavailable item cannot be
 * transferred between shops.
 */
final class ItemNotTransferable extends DomainException
{
    public static function forItem(int $itemId): self
    {
        return new self("Inventory item [{$itemId}] cannot be transferred in its current state.");
    }
}
