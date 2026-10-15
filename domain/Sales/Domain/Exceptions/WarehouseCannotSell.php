<?php

declare(strict_types=1);

namespace Domain\Sales\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

/** A warehouse holds stock and transfers it to shops; sales happen in shops. */
final class WarehouseCannotSell extends DomainException
{
    public static function forShop(int $shopId): self
    {
        return new self("Shop [{$shopId}] is a warehouse. Sales happen in a shop, so transfer the stock there first.");
    }
}
