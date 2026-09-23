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

/** A staff-recipient notification is a real delivery path now, not the old "unsupported recipient" permanent failure. */
class StaffRecipientDeliveryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_processing_a_staff_recipient_notification_delivers_it(): void
    {
        $owner = StaffRecord::factory()->create();

        $id = app(RequestNotificationHandler::class)->handle(new RequestNotificationCommand(
            eventType: 'CheckoutDiscountApplied',
            sourceModule: 'Sales',
            sourceId: 1,
            recipientType: 'staff',
            recipientId: $owner->id,
            category: 'operational',
            payload: ['shop_id' => 1, 'amount_minor' => 1000],
        ));

        app(NotificationEngine::class)->process($id, 1);

        $this->assertDatabaseHas('notification_delivery_attempts', [
            'notification_event_id' => $id,
            'channel' => 'in_app',
            'status' => 'delivered',
        ]);
    }

    public function test_a_repair_payment_approval_email_links_directly_to_the_repair(): void
    {
        Mail::fake();

        $owner = StaffRecord::factory()->create();

        $id = app(RequestNotificationHandler::class)->handle(new RequestNotificationCommand(
            eventType: 'RepairPaymentApprovalRequested',
            sourceModule: 'Repair',
            sourceId: 42,
            recipientType: 'staff',
            recipientId: $owner->id,
            category: 'operational',
            payload: ['shop_id' => 1],
        ));

        app(NotificationEngine::class)->process($id, 1);

        Mail::assertSent(NotificationMailable::class, function (NotificationMailable $mail) {
            return str_contains($mail->render(), '/admin/repairs/42');
        });
    }
}
