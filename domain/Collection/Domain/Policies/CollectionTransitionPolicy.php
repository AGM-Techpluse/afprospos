<?php

declare(strict_types=1);

namespace Domain\Collection\Domain\Policies;

/** The collection case state machine's legal edges (DBDD §12.1), mirrors PaymentTransitionPolicy/RepairTransitionPolicy's shape exactly. */
final class CollectionTransitionPolicy
{
    private const TRANSITIONS = [
        'pending' => ['overdue', 'resolved'],
        'overdue' => ['abandoned', 'resolved'],
        'abandoned' => ['resolved'],
        'resolved' => [],
    ];

    public function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }
}
