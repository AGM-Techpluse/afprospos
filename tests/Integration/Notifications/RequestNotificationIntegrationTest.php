<?php

declare(strict_types=1);

namespace Tests\Integration\Notifications;

use Domain\Notifications\Application\Commands\RequestNotificationCommand;
use Domain\Notifications\Application\Handlers\RequestNotificationHandler;
use Domain\Notifications\Infrastructure\Jobs\DispatchNotificationJob;
use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RequestNotificationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_requesting_a_notification_queues_it_and_dispatches_the_delivery_job(): void
    {
        Queue::fake();

        $customer = CustomerRecord::factory()->create();

        $id = app(RequestNotificationHandler::class)->handle(new RequestNotificationCommand(
            eventType: 'RepairCompleted',
            sourceModule: 'Repair',
            sourceId: 123,
            recipientType: 'customer',
            recipientId: $customer->id,
            category: 'transactional',
            payload: ['device_make' => 'Apple', 'device_model' => 'iPhone 12'],
        ));

        $this->assertDatabaseHas('notification_events', [
            'id' => $id,
            'event_type' => 'RepairCompleted',
            'source_module' => 'Repair',
            'source_id' => 123,
            'recipient_type' => 'customer',
            'recipient_id' => $customer->id,
            'status' => 'queued',
        ]);

        Queue::assertPushed(DispatchNotificationJob::class, fn (DispatchNotificationJob $job): bool => $job->notificationEventId === $id);
    }
}
