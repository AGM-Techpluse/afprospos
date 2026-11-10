<?php

declare(strict_types=1);

namespace Domain\Shop\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;
use Domain\Shared\Domain\ValueObjects\ShopId;

final class ShopAlreadyActive extends DomainException
{
    public static function forShop(ShopId $id): self
    {
        return new self("Shop [{$id->value}] is already active.");
    }
}
