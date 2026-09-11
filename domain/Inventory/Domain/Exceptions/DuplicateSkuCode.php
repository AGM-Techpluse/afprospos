<?php

declare(strict_types=1);

namespace Domain\Inventory\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

/** INV-BR-08: SKUs are unique across the business. */
final class DuplicateSkuCode extends DomainException
{
    public static function forCode(string $skuCode): self
    {
        return new self("A SKU already exists with code [{$skuCode}].");
    }
}
