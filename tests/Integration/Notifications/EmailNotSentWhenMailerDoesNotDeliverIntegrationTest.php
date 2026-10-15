<?php

declare(strict_types=1);

namespace Tests\Integration\Notifications;

use Domain\Notifications\Application\Commands\RequestNotificationCommand;
use Domain\Notifications\Application\Handlers\RequestNotificationHandler;
use Domain\Notifications\Application\Services\NotificationEngine;
use Domain\Notifications\Infrastructure\Channels\Email\NotificationMailable;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** A `log` or `array` mailer must never be recorded as a delivered email (the old false-success bug). */
class EmailNotSentWhenMailerDoesNotDeliverIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_log_mailer_records_the_email_as_not_sent_and_never_as_delivered(): void
    {
        Mail::fake();
        config(['mail.default' => 'log']);

        $owner = StaffRecord::factory()->create();

        $id = app(RequestNotificationHandler::class)->handle(new RequestNotificationCommand(
            eventType: 'RepairPaymentApprovalRequested',
            sourceModule: 'Repair',
            sourceId: 7,
            recipientType: 'staff',
            recipientId: $owner->id,
            category: 'operational',
            payload: ['shop_id' => 1],
        ));

        app(NotificationEngine::class)->process($id, 1);

        Mail::assertNotSent(NotificationMailable::class);
        $this->assertDatabaseHas('notification_delivery_attempts', [
            'notification_event_id' => $id,
            'channel' => 'email',
            'status' => 'failed_permanent',
        ]);
        $this->assertDatabaseMissing('notification_delivery_attempts', [
            'notification_event_id' => $id,
            'channel' => 'email',
            'status' => 'delivered',
        ]);
    }
}
