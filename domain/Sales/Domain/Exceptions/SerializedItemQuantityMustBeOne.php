<?php

declare(strict_types=1);

namespace Domain\Sales\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

/**
 * A serialized (IMEI-tracked) SKU reserves exactly one distinct unit
 * per checkout line — a cashier adding two units of the same serialized
 * SKU adds two separate lines, one physical unit each.
 */
final class SerializedItemQuantityMustBeOne extends DomainException
{
    public static function forSku(int $skuId): self
    {
        return new self("SKU [{$skuId}] is serialized — each checkout line must reserve exactly 1 unit.");
    }
}
