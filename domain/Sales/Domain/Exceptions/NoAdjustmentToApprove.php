<?php

declare(strict_types=1);

namespace Domain\Sales\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

/** Approving a discount on a checkout that doesn't currently carry one. */
final class NoAdjustmentToApprove extends DomainException
{
    public static function forCheckout(int $checkoutId): self
    {
        return new self("Checkout [{$checkoutId}] has no discount to approve.");
    }
}
