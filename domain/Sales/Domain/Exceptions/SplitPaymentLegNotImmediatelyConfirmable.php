<?php

declare(strict_types=1);

namespace Domain\Sales\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

/**
 * A bank-transfer leg never confirms immediately (it waits on a real bank
 * reference being verified later, same as the existing single-method
 * checkout flow) — submitted as ONE of several split-payment legs, the
 * submission could end up with some legs confirmed and this one still
 * pending indefinitely, which is a confusing half-paid state to be in.
 * Bank transfer is fine alone (the existing pending-confirmation path),
 * just not mixed into a multi-method split.
 */
final class SplitPaymentLegNotImmediatelyConfirmable extends DomainException
{
    public static function forMethod(string $method): self
    {
        return new self("Payment method [{$method}] cannot be combined with other methods in a split payment — it isn't confirmed immediately.");
    }
}
