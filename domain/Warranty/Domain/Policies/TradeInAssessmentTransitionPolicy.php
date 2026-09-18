<?php

declare(strict_types=1);

namespace Domain\Warranty\Domain\Policies;

/** DBDD §15.4's `resolution_state` enum. */
final class TradeInAssessmentTransitionPolicy
{
    private const TRANSITIONS = [
        'submitted' => ['assessed'],
        'assessed' => ['approved', 'rejected'],
        'approved' => ['applied'],
        'rejected' => [],
        'applied' => [],
    ];

    public function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }
}
