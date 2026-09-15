<?php

declare(strict_types=1);

namespace Tests\Integration\Repair;

use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Two-tick buffer: awaiting_authorization -> payment_overdue on first detection, payment_overdue -> expired_cancelled on the next run. */
class ExpireRepairAuthorizationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_job_past_its_deadline_becomes_payment_overdue_then_expired_cancelled_on_the_next_run(): void
    {
        $job = RepairJobRecord::factory()->create([
            'repair_status' => 'awaiting_authorization',
            'down_payment_required_minor' => 100000,
            'down_payment_deadline_at' => now()->subHour(),
        ]);

        $this->artisan('afprospos:repairs:expire-authorizations')->assertSuccessful();
        $this->assertDatabaseHas('repair_jobs', ['id' => $job->id, 'repair_status' => 'payment_overdue']);

        $this->artisan('afprospos:repairs:expire-authorizations')->assertSuccessful();
        $this->assertDatabaseHas('repair_jobs', ['id' => $job->id, 'repair_status' => 'expired_cancelled']);
    }

    public function test_a_job_not_yet_past_its_deadline_is_left_alone(): void
    {
        $job = RepairJobRecord::factory()->create([
            'repair_status' => 'awaiting_authorization',
            'down_payment_deadline_at' => now()->addDay(),
        ]);

        $this->artisan('afprospos:repairs:expire-authorizations')->assertSuccessful();

        $this->assertDatabaseHas('repair_jobs', ['id' => $job->id, 'repair_status' => 'awaiting_authorization']);
    }
}
