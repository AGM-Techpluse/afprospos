<?php

declare(strict_types=1);

namespace Domain\Sales\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

/** MKT-BR-15: a checkout carrying an unapproved discount/adjustment cannot be paid. */
final class DiscountNotApproved extends DomainException
{
    public static function forCheckout(int $checkoutId): self
    {
        return new self("Checkout [{$checkoutId}] has a discount pending Shop Owner approval.");
    }
}
