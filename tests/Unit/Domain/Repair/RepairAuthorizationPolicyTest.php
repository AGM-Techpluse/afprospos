<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Repair;

use DateTimeImmutable;
use Domain\Repair\Domain\Policies\RepairAuthorizationPolicy;
use Tests\TestCase;

class RepairAuthorizationPolicyTest extends TestCase
{
    public function test_null_deadline_is_always_within_window(): void
    {
        $this->assertTrue((new RepairAuthorizationPolicy)->isWithinDeadline(null, new DateTimeImmutable));
    }

    public function test_a_deadline_in_the_future_is_within_window(): void
    {
        $now = new DateTimeImmutable('2026-01-01 00:00:00');
        $deadline = new DateTimeImmutable('2026-01-02 00:00:00');

        $this->assertTrue((new RepairAuthorizationPolicy)->isWithinDeadline($deadline, $now));
    }

    public function test_a_deadline_in_the_past_is_not_within_window(): void
    {
        $now = new DateTimeImmutable('2026-01-02 00:00:00');
        $deadline = new DateTimeImmutable('2026-01-01 00:00:00');

        $this->assertFalse((new RepairAuthorizationPolicy)->isWithinDeadline($deadline, $now));
    }
}
