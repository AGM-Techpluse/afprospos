<?php

declare(strict_types=1);

namespace Tests\Integration\Notifications;

use Domain\Notifications\Application\Commands\RecordDeliveryAttemptCommand;
use Domain\Notifications\Application\Handlers\RecordDeliveryAttemptHandler;
use Domain\Notifications\Infrastructure\Persistence\Eloquent\NotificationEventRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecordDeliveryAttemptIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_delivered_attempt_marks_the_notification_event_delivered(): void
    {
        $notificationEvent = NotificationEventRecord::factory()->create();

        app(RecordDeliveryAttemptHandler::class)->handle(
            new RecordDeliveryAttemptCommand(
                notificationEventId: $notificationEvent->id,
                channel: 'email',
                provider: 'resend',
                attemptNumber: 1,
                status: 'delivered',
                providerResponse: null,
            ),
            maxAttempts: 3,
        );

        $this->assertDatabaseHas('notification_events', ['id' => $notificationEvent->id, 'status' => 'delivered']);
        $this->assertDatabaseHas('notification_delivery_attempts', [
            'notification_event_id' => $notificationEvent->id,
            'channel' => 'email',
            'provider' => 'resend',
            'status' => 'delivered',
        ]);
    }

    public function test_a_transient_failure_with_attempts_remaining_stays_queued(): void
    {
        $notificationEvent = NotificationEventRecord::factory()->create();

        app(RecordDeliveryAttemptHandler::class)->handle(
            new RecordDeliveryAttemptCommand(
                notificationEventId: $notificationEvent->id,
                channel: 'email',
                provider: 'resend',
                attemptNumber: 1,
                status: 'failed_transient',
                providerResponse: 'Connection timed out',
            ),
            maxAttempts: 3,
        );

        $this->assertDatabaseHas('notification_events', ['id' => $notificationEvent->id, 'status' => 'queued']);
    }

    public function test_a_transient_failure_with_attempts_exhausted_marks_failed_exhausted(): void
    {
        $notificationEvent = NotificationEventRecord::factory()->create();

        app(RecordDeliveryAttemptHandler::class)->handle(
            new RecordDeliveryAttemptCommand(
                notificationEventId: $notificationEvent->id,
                channel: 'email',
                provider: 'resend',
                attemptNumber: 3,
                status: 'failed_transient',
                providerResponse: 'Connection timed out',
            ),
            maxAttempts: 3,
        );

        $this->assertDatabaseHas('notification_events', ['id' => $notificationEvent->id, 'status' => 'failed_exhausted']);
    }

    public function test_a_permanent_failure_marks_failed_exhausted_immediately(): void
    {
        $notificationEvent = NotificationEventRecord::factory()->create();

        app(RecordDeliveryAttemptHandler::class)->handle(
            new RecordDeliveryAttemptCommand(
                notificationEventId: $notificationEvent->id,
                channel: 'email',
                provider: 'resend',
                attemptNumber: 1,
                status: 'failed_permanent',
                providerResponse: 'Invalid recipient address',
            ),
            maxAttempts: 3,
        );

        $this->assertDatabaseHas('notification_events', ['id' => $notificationEvent->id, 'status' => 'failed_exhausted']);
    }
}
