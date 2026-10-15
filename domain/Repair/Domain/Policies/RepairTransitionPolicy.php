<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Policies;

/**
 * The repair job state machine's legal edges, a pure predicate over a const
 * transition table reused by every RepairJob mutator.
 *
 * Flow (as run in the shop):
 *   received → diagnosing (functional test & diagnosis)
 *   → awaiting_authorization (quote & authorization: cashier enters the amount,
 *     manager approves or declines)
 *   → in_progress (the repair; parts are fitted here)
 *   → qc_pending (quality check) → handover (frontdesk hands over, full payment
 *     approved) → completed (set when the device is released from collection).
 * A failed quality check either returns the job to diagnosing or cancels it, as the
 * admin configures. Any open job can be cancelled. Quote declined = cancelled.
 */
final class RepairTransitionPolicy
{
    private const TRANSITIONS = [
        'received' => ['diagnosing', 'cancelled'],
        'diagnosing' => ['diagnosing', 'awaiting_authorization', 'unrepairable', 'cancelled'],
        'awaiting_authorization' => ['in_progress', 'payment_overdue', 'cancelled'],
        'payment_overdue' => ['in_progress', 'expired_cancelled', 'cancelled'],
        'expired_cancelled' => [],
        // Legacy state kept so existing rows still move on; new jobs go straight to in_progress.
        'awaiting_parts' => ['in_progress', 'cancelled'],
        'in_progress' => ['qc_pending', 'failed_requires_resolution', 'cancelled'],
        'qc_pending' => ['handover', 'diagnosing', 'cancelled'],
        'handover' => ['completed'],
        'completed' => [],
        'unrepairable' => [],
        'failed_requires_resolution' => ['in_progress', 'unrepairable', 'cancelled'],
        'cancelled' => [],
    ];

    public function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }
}
