<?php

declare(strict_types=1);

namespace Domain\Sales\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

/** DBDD §40: only an `open` checkout may be mutated (items/adjustments added, cancelled). */
final class CheckoutNotOpen extends DomainException
{
    public static function forCheckout(int $checkoutId, string $status): self
    {
        return new self("Checkout [{$checkoutId}] is not open (status: {$status}).");
    }
}
