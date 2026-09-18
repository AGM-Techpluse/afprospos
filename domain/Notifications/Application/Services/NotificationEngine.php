<?php

declare(strict_types=1);

namespace Domain\Notifications\Application\Services;

use Domain\Identity\Application\Contracts\CustomerDirectoryQuery;
use Domain\Notifications\Application\Commands\RecordDeliveryAttemptCommand;
use Domain\Notifications\Application\Contracts\InAppChannel;
use Domain\Notifications\Application\DTOs\DeliveryResult;
use Domain\Notifications\Application\DTOs\NotificationMessage;
use Domain\Notifications\Application\Handlers\RecordDeliveryAttemptHandler;
use Domain\Notifications\Domain\Entities\NotificationEvent;
use Domain\Notifications\Domain\Repositories\NotificationEventRepository;
use Domain\Notifications\Domain\ValueObjects\NotificationEventId;
use Domain\Notifications\Infrastructure\Channels\Email\Resend\ResendMailer;
use Domain\Notifications\Infrastructure\Channels\Email\Smtp\SmtpMailer;

/**
 * ADD §21A's "Notification Engine": given a queued NotificationEvent, resolves
 * the recipient, picks channels for the category, and tries email providers
 * in priority order (Resend, then the app's normally-configured mailer via
 * SmtpMailer — NOTIF-BR-08 failover) plus the always-available in-app
 * channel (NOTIF-BR-18). Customer-recipient only this slice — see the
 * plan's "Scope cuts" note for staff recipients.
 */
final class NotificationEngine
{
    /** @var array<string, string> event_type => [title template, body template] built from payload */
    private const MESSAGE_TEMPLATES = [
        'RepairCompleted' => ['Your repair is complete', 'Your %s %s is ready — repair job #%d.'],
        'RepairReadyForCollection' => ['Ready for collection', 'Your %s %s is ready for collection.'],
        'WarrantyClaimSubmitted' => ['Warranty claim received', 'We received your warranty claim #%d and will assess it shortly.'],
        'WarrantyClaimResolved' => ['Warranty claim resolved', 'Your warranty claim #%d has been resolved.'],
        'SaleCompleted' => ['Thank you for your purchase', 'Your receipt%s is ready to view in your Orders.'],
        'ReturnRequestResolved' => ['Return resolved', 'Your return request #%d has been resolved.'],
        'TradeInCreditApplied' => ['Trade-in credit applied', 'Your trade-in #%d credit has been applied to your purchase.'],
    ];

    public function __construct(
        private readonly NotificationEventRepository $notificationEvents,
        private readonly CustomerDirectoryQuery $customers,
        private readonly ResendMailer $resend,
        private readonly SmtpMailer $smtp,
        private readonly InAppChannel $inApp,
        private readonly RecordDeliveryAttemptHandler $recordDeliveryAttempt,
    ) {}

    public function process(int $notificationEventId, int $attemptNumber): void
    {
        $notificationEvent = $this->notificationEvents->get(new NotificationEventId($notificationEventId));

        if ($notificationEvent->status() !== 'queued') {
            // Already resolved by a previous, still-in-flight attempt — nothing to do.
            return;
        }

        if ($notificationEvent->recipientType() !== 'customer') {
            // Staff recipients aren't wired this slice (see plan's Scope cuts) — never let an
            // unsupported recipient type sit `queued` forever.
            $this->record($notificationEventId, 'in_app', 'none', $attemptNumber, DeliveryResult::failedPermanent('none', 'Staff recipients are not supported yet.'));

            return;
        }

        $customer = $this->customers->find($notificationEvent->recipientId());

        if ($customer === null) {
            $this->record($notificationEventId, 'in_app', 'none', $attemptNumber, DeliveryResult::failedPermanent('none', 'Recipient customer no longer exists.'));

            return;
        }

        $message = $this->buildMessage($notificationEvent, $customer);
        $mandatory = in_array($notificationEvent->category(), config('afprospos.notifications.mandatory_categories', []), true);
        $emailEnabled = $mandatory || (bool) ($customer['notification_preferences']['email'] ?? true);

        if ($emailEnabled) {
            $this->sendEmail($notificationEventId, $attemptNumber, $message);
        }

        // In-app is always recorded regardless of preference — NOTIF-BR-18.
        $this->record($notificationEventId, 'in_app', 'in_app', $attemptNumber, $this->inApp->send($message));
    }

    private function sendEmail(int $notificationEventId, int $attemptNumber, NotificationMessage $message): void
    {
        if ($this->resend->isConfigured()) {
            $result = $this->resend->send($message);

            if ($result->isDelivered()) {
                $this->record($notificationEventId, 'email', 'resend', $attemptNumber, $result);

                return;
            }
        }

        $this->record($notificationEventId, 'email', 'smtp', $attemptNumber, $this->smtp->send($message));
    }

    private function record(int $notificationEventId, string $channel, string $provider, int $attemptNumber, DeliveryResult $result): void
    {
        $this->recordDeliveryAttempt->handle(
            new RecordDeliveryAttemptCommand(
                notificationEventId: $notificationEventId,
                channel: $channel,
                provider: $result->provider,
                attemptNumber: $attemptNumber,
                status: $result->status,
                providerResponse: $result->providerResponse,
            ),
            maxAttempts: (int) config('afprospos.notifications.max_delivery_attempts', 3),
        );
    }

    /** @param array{id:int,name:string,email:?string,phone:string,notification_preferences?:array,marketing_opt_out?:bool} $customer */
    private function buildMessage(NotificationEvent $notificationEvent, array $customer): NotificationMessage
    {
        [$title, $bodyTemplate] = self::MESSAGE_TEMPLATES[$notificationEvent->eventType()] ?? [$notificationEvent->eventType(), '%s'];
        $payload = $notificationEvent->payload();

        $body = match ($notificationEvent->eventType()) {
            'RepairCompleted', 'RepairReadyForCollection' => sprintf($bodyTemplate, $payload['device_make'] ?? '', $payload['device_model'] ?? '', $notificationEvent->sourceId()),
            'SaleCompleted' => sprintf($bodyTemplate, isset($payload['invoice_number']) ? " (invoice {$payload['invoice_number']})" : ''),
            default => sprintf($bodyTemplate, $notificationEvent->sourceId()),
        };

        return new NotificationMessage(
            notificationEventId: $notificationEvent->id()?->value ?? 0,
            recipientEmail: $customer['email'] ?? '',
            recipientPhone: $customer['phone'] ?? '',
            recipientName: $customer['name'] ?? '',
            title: $title,
            body: $body,
        );
    }
}
