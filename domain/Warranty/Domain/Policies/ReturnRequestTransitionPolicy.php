<?php

declare(strict_types=1);

namespace Domain\Warranty\Domain\Policies;

/** DBDD §15.3's `resolution_state` enum. `denied -> approved` is the WAR-BR-12 administrative-override path only, never a normal re-assessment. */
final class ReturnRequestTransitionPolicy
{
    private const TRANSITIONS = [
        'requested' => ['under_assessment'],
        'under_assessment' => ['approved', 'denied'],
        'approved' => ['resolved'],
        'denied' => ['approved'],
        'resolved' => [],
    ];

    public function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }
}
