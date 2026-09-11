<?php

declare(strict_types=1);

namespace Domain\Inventory\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

/**
 * BLD §2.5 / INV-BR-15: Available = On-hand - Reserved must never go
 * negative — a race that would over-commit stock is rejected, not
 * silently allowed.
 */
final class InsufficientAvailableStock extends DomainException
{
    public static function forSku(int $skuId, int $shopId, int $requested, int $available): self
    {
        return new self(
            "Cannot reserve {$requested} unit(s) of SKU [{$skuId}] at shop [{$shopId}]: only {$available} available."
        );
    }
}
