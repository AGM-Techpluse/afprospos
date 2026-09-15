<?php

declare(strict_types=1);

namespace Domain\Payments\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

/**
 * Signals a duplicate confirm call, not a real error — DBDD §16.2: "the
 * application must not change an already-confirmed payment to a
 * different terminal state simply because a duplicate ... callback
 * arrived." `ConfirmPaymentHandler` catches this and treats it as an
 * idempotent success rather than letting it surface as a failure.
 */
final class PaymentAlreadyConfirmed extends DomainException
{
    public static function forTransaction(int $transactionId): self
    {
        return new self("Payment transaction [{$transactionId}] is already confirmed.");
    }
}
