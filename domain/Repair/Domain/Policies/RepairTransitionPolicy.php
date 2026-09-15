<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Policies;

/**
 * The repair job state machine's legal edges (DBDD §17.1), mirrors
 * PaymentTransitionPolicy's shape exactly — a pure predicate over a
 * const transition table, reused by every RepairJob mutator.
 */
final class RepairTransitionPolicy
{
    private const TRANSITIONS = [
        'received' => ['diagnosing'],
        'diagnosing' => ['diagnosing', 'awaiting_authorization', 'unrepairable'],
        'awaiting_authorization' => ['awaiting_parts', 'payment_overdue'],
        'payment_overdue' => ['awaiting_parts', 'expired_cancelled'],
        'expired_cancelled' => [],
        'awaiting_parts' => ['in_progress'],
        'in_progress' => ['completed', 'failed_requires_resolution'],
        'completed' => [],
        'unrepairable' => [],
        'failed_requires_resolution' => ['in_progress', 'unrepairable'],
    ];

    public function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }
}
