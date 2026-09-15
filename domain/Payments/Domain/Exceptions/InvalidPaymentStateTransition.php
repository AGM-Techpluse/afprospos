<?php

declare(strict_types=1);

namespace Domain\Payments\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

/** DBDD §16.2's state machine — a transition not in `PaymentTransitionPolicy`'s table is always illegal, never silently coerced. */
final class InvalidPaymentStateTransition extends DomainException
{
    public static function forTransaction(int $transactionId, string $from, string $to): self
    {
        return new self("Payment transaction [{$transactionId}] cannot transition from [{$from}] to [{$to}].");
    }
}
