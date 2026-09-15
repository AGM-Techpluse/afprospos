<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Repair;

use Domain\Repair\Domain\Policies\RepairTransitionPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RepairTransitionPolicyTest extends TestCase
{
    #[DataProvider('legalTransitions')]
    public function test_legal_transitions_are_allowed(string $from, string $to): void
    {
        $this->assertTrue((new RepairTransitionPolicy)->canTransition($from, $to));
    }

    #[DataProvider('illegalTransitions')]
    public function test_illegal_transitions_are_rejected(string $from, string $to): void
    {
        $this->assertFalse((new RepairTransitionPolicy)->canTransition($from, $to));
    }

    public static function legalTransitions(): array
    {
        return [
            'received -> diagnosing' => ['received', 'diagnosing'],
            'diagnosing -> diagnosing' => ['diagnosing', 'diagnosing'],
            'diagnosing -> awaiting_authorization' => ['diagnosing', 'awaiting_authorization'],
            'diagnosing -> unrepairable' => ['diagnosing', 'unrepairable'],
            'awaiting_authorization -> awaiting_parts' => ['awaiting_authorization', 'awaiting_parts'],
            'awaiting_authorization -> payment_overdue' => ['awaiting_authorization', 'payment_overdue'],
            'payment_overdue -> awaiting_parts' => ['payment_overdue', 'awaiting_parts'],
            'payment_overdue -> expired_cancelled' => ['payment_overdue', 'expired_cancelled'],
            'awaiting_parts -> in_progress' => ['awaiting_parts', 'in_progress'],
            'in_progress -> completed' => ['in_progress', 'completed'],
            'in_progress -> failed_requires_resolution' => ['in_progress', 'failed_requires_resolution'],
            'failed_requires_resolution -> in_progress' => ['failed_requires_resolution', 'in_progress'],
            'failed_requires_resolution -> unrepairable' => ['failed_requires_resolution', 'unrepairable'],
        ];
    }

    public static function illegalTransitions(): array
    {
        return [
            'received -> awaiting_authorization (skips diagnosis)' => ['received', 'awaiting_authorization'],
            'awaiting_parts -> awaiting_authorization (backwards)' => ['awaiting_parts', 'awaiting_authorization'],
            'completed -> anything' => ['completed', 'in_progress'],
            'unrepairable -> anything' => ['unrepairable', 'diagnosing'],
            'expired_cancelled -> anything' => ['expired_cancelled', 'awaiting_parts'],
            'in_progress -> awaiting_parts (backwards)' => ['in_progress', 'awaiting_parts'],
        ];
    }
}
