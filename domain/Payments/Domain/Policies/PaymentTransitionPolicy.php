<?php

declare(strict_types=1);

namespace Domain\Payments\Domain\Policies;

/**
 * The one place the payment state machine's legal edges live (DBDD §16.2),
 * reused by every `PaymentTransaction` mutator — mirrors
 * `CheckoutReservationPolicy`'s "policy as a pure predicate" shape,
 * generalized to a full table since Payments' state space is materially
 * more branched than Checkout's binary open/not-open check.
 *
 * `confirmed -> confirmed` is deliberately NOT listed here — that
 * duplicate-call case is handled by the entity as `PaymentAlreadyConfirmed`,
 * an idempotent no-op, not a legal "transition."
 */
final class PaymentTransitionPolicy
{
    private const TRANSITIONS = [
        'pending' => ['confirmed', 'payment_pending_confirmation', 'exception'],
        'payment_pending_confirmation' => ['confirmed', 'exception', 'disputed'],
        'confirmed' => ['disputed', 'refunded'],
        'disputed' => ['confirmed', 'refunded', 'exception'],
        'refunded' => [],
        'exception' => [],
    ];

    public function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }
}
