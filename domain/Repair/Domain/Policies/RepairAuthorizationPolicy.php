<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Policies;

use DateTimeImmutable;

/**
 * An authorization/down-payment confirmation may only be granted before
 * its deadline has passed (DBDD §11.1's down_payment_deadline_at doubles
 * as the authorization-response deadline — Phase 6 scope decision).
 * Mirrors CheckoutReservationPolicy's "one comparison, reused everywhere" shape.
 */
final class RepairAuthorizationPolicy
{
    public function isWithinDeadline(?DateTimeImmutable $deadline, DateTimeImmutable $now): bool
    {
        return $deadline === null || $now < $deadline;
    }
}
