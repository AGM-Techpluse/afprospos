<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

/** Repairs are received in a shop. A warehouse only stores and transfers parts. */
final class WarehouseCannotTakeRepairs extends DomainException
{
    public static function forShop(int $shopId): self
    {
        return new self("Shop [{$shopId}] is a warehouse. Repairs are received in a shop, so send the device to a shop first.");
    }
}
