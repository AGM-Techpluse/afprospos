<?php

declare(strict_types=1);

namespace Domain\Sales\Domain\Policies;

use DateTimeImmutable;

/**
 * A checkout may only be mutated (items/adjustments added or removed)
 * or converted to a sale while it is `open` and its reservation has not
 * yet passed `reservation_expires_at` (DBDD §40: "paid checkout cannot
 * expire; expired checkout cannot become paid"). The authoritative
 * enforcement is always a locked read immediately before mutation
 * (SalesCheckout's own guard methods) — this Policy is the one place
 * that comparison lives, reused by both.
 */
final class CheckoutReservationPolicy
{
    public function isMutable(string $status, DateTimeImmutable $expiresAt, DateTimeImmutable $now): bool
    {
        return $status === 'open' && $now < $expiresAt;
    }
}
