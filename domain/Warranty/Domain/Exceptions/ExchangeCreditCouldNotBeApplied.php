<?php

declare(strict_types=1);

namespace Domain\Warranty\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

final class ExchangeCreditCouldNotBeApplied extends DomainException
{
    public static function checkoutNotOpen(int $checkoutId): self
    {
        return new self("Checkout [{$checkoutId}] is not open and cannot accept an exchange credit.");
    }

    public static function saleNotFound(int $saleId): self
    {
        return new self("Sale [{$saleId}] could not be found.");
    }
}
