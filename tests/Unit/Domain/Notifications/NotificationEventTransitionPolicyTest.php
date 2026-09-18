<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Notifications;

use Domain\Notifications\Domain\Policies\NotificationEventTransitionPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NotificationEventTransitionPolicyTest extends TestCase
{
    #[DataProvider('legalTransitions')]
    public function test_legal_transitions_are_allowed(string $from, string $to): void
    {
        $this->assertTrue((new NotificationEventTransitionPolicy)->canTransition($from, $to));
    }

    #[DataProvider('illegalTransitions')]
    public function test_illegal_transitions_are_rejected(string $from, string $to): void
    {
        $this->assertFalse((new NotificationEventTransitionPolicy)->canTransition($from, $to));
    }

    public static function legalTransitions(): array
    {
        return [
            'queued -> delivered' => ['queued', 'delivered'],
            'queued -> failed_exhausted' => ['queued', 'failed_exhausted'],
        ];
    }

    public static function illegalTransitions(): array
    {
        return [
            'delivered -> queued' => ['delivered', 'queued'],
            'delivered -> failed_exhausted' => ['delivered', 'failed_exhausted'],
            'failed_exhausted -> queued' => ['failed_exhausted', 'queued'],
            'failed_exhausted -> delivered' => ['failed_exhausted', 'delivered'],
        ];
    }
}
