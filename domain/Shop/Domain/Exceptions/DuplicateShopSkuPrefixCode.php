<?php

declare(strict_types=1);

namespace Domain\Shop\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

/**
 * BRD SKU-04 / INV-BR-08: the shop's SKU prefix code feeds directly into
 * business-wide SKU generation, so a collision here would eventually
 * produce colliding SKUs across two different shops.
 */
final class DuplicateShopSkuPrefixCode extends DomainException
{
    public static function forCode(string $code): self
    {
        return new self("A shop already uses SKU prefix code [{$code}].");
    }
}
