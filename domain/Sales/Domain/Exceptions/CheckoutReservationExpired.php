<?php

declare(strict_types=1);

namespace Domain\Sales\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

/**
 * SALE/PAY-BR-08: a payment confirmation received after its reservation
 * has already expired must not silently complete the sale against
 * released inventory, and must not silently fail either — it is routed
 * here as an explicit exception so the caller can surface a clear
 * payment-exception path instead of guessing what happened.
 */
final class CheckoutReservationExpired extends DomainException
{
    public static function forCheckout(int $checkoutId): self
    {
        return new self("Checkout [{$checkoutId}]'s reservation has already expired and cannot be paid — route to the payment exception process.");
    }
}
