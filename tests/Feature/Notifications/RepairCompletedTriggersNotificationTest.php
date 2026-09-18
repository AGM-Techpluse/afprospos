<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use Carbon\CarbonImmutable;
use Domain\Repair\Domain\Events\RepairCompleted;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Proves the outbox listener wiring itself, not CompleteRepairHandler's own
 * (already-tested) business logic — dispatches the real event RepairCompleted
 * already fires and asserts Notifications' listener turns it into a durable
 * notification_events row. No changes to the Repair module were needed.
 */
class RepairCompletedTriggersNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_repair_completed_creates_a_queued_notification_event_for_the_customer(): void
    {
        $repairJob = RepairJobRecord::factory()->create([
            'device_make' => 'Apple',
            'device_model' => 'iPhone 12',
            'repair_status' => 'completed',
        ]);

        Event::dispatch(new RepairCompleted($repairJob->id, 'settled', CarbonImmutable::now()));

        $this->assertDatabaseHas('notification_events', [
            'event_type' => 'RepairCompleted',
            'source_module' => 'Repair',
            'source_id' => $repairJob->id,
            'recipient_type' => 'customer',
            'recipient_id' => $repairJob->customer_id,
            'category' => 'transactional',
        ]);
    }
}
