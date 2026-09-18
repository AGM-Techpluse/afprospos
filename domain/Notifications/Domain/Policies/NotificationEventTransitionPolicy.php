<?php

declare(strict_types=1);

namespace Domain\Notifications\Domain\Policies;

/** DBDD §20.1's `status` enum. Both outcomes are terminal — a delivered notification never reverts, and an exhausted one only gets a fresh attempt via a brand-new NotificationEvent, not a re-transition of this one (mirrors the append-only spirit of Audit, not a full retry-in-place state machine). */
final class NotificationEventTransitionPolicy
{
    private const TRANSITIONS = [
        'queued' => ['delivered', 'failed_exhausted'],
        'delivered' => [],
        'failed_exhausted' => [],
    ];

    public function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }
}
