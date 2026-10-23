<?php

declare(strict_types=1);

namespace Domain\Sales\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

/** A customer's self-service cart is shop-scoped, same as a POS checkout — a till can't silently absorb it into a sale at a different shop. */
final class CustomerCartReservedAtAnotherShop extends DomainException
{
    public static function forCheckout(int $checkoutId): self
    {
        return new self("Checkout [{$checkoutId}] is reserved at a different shop — ask the customer to clear or complete it there first.");
    }
}
