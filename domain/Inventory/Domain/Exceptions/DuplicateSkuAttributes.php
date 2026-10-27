<?php

declare(strict_types=1);

namespace Domain\Inventory\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

/** INV-BR-08: one SKU per exact product configuration — editing a SKU's attributes to match another already-existing SKU under the same product would merge two SKUs with separate stock/pricing histories, which isn't a safe automatic action. */
final class DuplicateSkuAttributes extends DomainException
{
    public static function forProduct(int $productId): self
    {
        return new self("Another variant of this product already has these exact attributes (product #{$productId}).");
    }
}
