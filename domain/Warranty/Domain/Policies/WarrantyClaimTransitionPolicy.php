<?php

declare(strict_types=1);

namespace Domain\Warranty\Domain\Policies;

/** DBDD §15.2's `resolution_state` enum — mirrors CollectionTransitionPolicy's shape. */
final class WarrantyClaimTransitionPolicy
{
    private const TRANSITIONS = [
        'submitted' => ['under_assessment', 'eligible', 'not_eligible'],
        'under_assessment' => ['eligible', 'not_eligible'],
        'eligible' => ['remedy_selected'],
        'not_eligible' => [],
        'remedy_selected' => ['resolved'],
        'resolved' => [],
    ];

    public function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }
}
