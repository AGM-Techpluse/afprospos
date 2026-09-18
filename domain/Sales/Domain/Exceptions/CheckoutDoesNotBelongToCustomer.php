<?php

declare(strict_types=1);

namespace Domain\Sales\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

/** Defense-in-depth for a customer-initiated write path — the Controller already checks ownership before dispatch, but the Handler (the actual write path) must not trust that alone. */
final class CheckoutDoesNotBelongToCustomer extends DomainException
{
    public static function forCheckout(int $checkoutId): self
    {
        return new self("Checkout [{$checkoutId}] does not belong to this customer.");
    }
}
