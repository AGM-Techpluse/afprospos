<?php

declare(strict_types=1);

namespace Domain\Sales\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

/** A customer/guest-originated checkout has no cashier to attribute a discount approval request to — discounting is a staff-POS action. */
final class DiscountRequiresACashier extends DomainException
{
    public static function forCheckout(int $checkoutId): self
    {
        return new self("Checkout [{$checkoutId}] has no cashier — discounts can only be applied at the till.");
    }
}
