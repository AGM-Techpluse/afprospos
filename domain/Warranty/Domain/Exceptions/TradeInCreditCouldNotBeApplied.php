<?php

declare(strict_types=1);

namespace Domain\Warranty\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

final class TradeInCreditCouldNotBeApplied extends DomainException
{
    public static function checkoutNotOpen(int $checkoutId): self
    {
        return new self("Checkout [{$checkoutId}] is not open and cannot accept a trade-in credit.");
    }
}
