<?php

declare(strict_types=1);

namespace Domain\Notifications\Application\Services;

use Domain\Identity\Application\Contracts\CustomerDirectoryQuery;
use Domain\Identity\Application\Contracts\StaffLookup;
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
 * channel (NOTIF-BR-18). Staff recipients always get email (no staff
 * notification-preference model exists yet, unlike customers' opt-out) —
 * simplest correct default until the business asks for one.
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
        'CheckoutDiscountApplied' => ['Discount awaiting your approval', 'A discount was applied to checkout #%d and needs your approval before it can be paid.'],
        'RepairPaymentApprovalRequested' => ['Repair payment awaiting your approval', 'Repair job #%d has a payment recording request awaiting your approval.'],
    ];

    /**
     * @var array<string, string> event_type => admin/customer path, `%d`
     *                            interpolated with the event's sourceId where the destination is
     *                            one specific record rather than a list page — so a notification
     *                            (email or in-app) always has somewhere concrete to click through
     *                            to, instead of leaving the recipient to guess where to look.
     */
    private const ACTION_URL_TEMPLATES = [
        'RepairCompleted' => '/customer/repairs',
        'RepairReadyForCollection' => '/customer/repairs',
        'WarrantyClaimSubmitted' => '/customer/warranty/claims',
        'WarrantyClaimResolved' => '/customer/warranty/claims',
        'SaleCompleted' => '/customer/orders',
        'ReturnRequestResolved' => '/customer/warranty/returns',
        'TradeInCreditApplied' => '/customer/orders',
        'CheckoutDiscountApplied' => '/admin/sales/checkout?checkout=%d',
        'RepairPaymentApprovalRequested' => '/admin/repairs/%d',
        'RepairDownPaymentApprovalRequested' => '/admin/repairs/%d',
    ];

    public function __construct(
        private readonly NotificationEventRepository $notificationEvents,
        private readonly CustomerDirectoryQuery $customers,
        private readonly StaffLookup $staff,
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

        match ($notificationEvent->recipientType()) {
            'customer' => $this->processForCustomer($notificationEvent, $notificationEventId, $attemptNumber),
            'staff' => $this->processForStaff($notificationEvent, $notificationEventId, $attemptNumber),
            default => $this->record($notificationEventId, 'in_app', 'none', $attemptNumber, DeliveryResult::failedPermanent('none', "Unsupported recipient type [{$notificationEvent->recipientType()}].")),
        };
    }

    private function processForCustomer(NotificationEvent $notificationEvent, int $notificationEventId, int $attemptNumber): void
    {
        $customer = $this->customers->find($notificationEvent->recipientId());

        if ($customer === null) {
            $this->record($notificationEventId, 'in_app', 'none', $attemptNumber, DeliveryResult::failedPermanent('none', 'Recipient customer no longer exists.'));

            return;
        }

        $message = $this->buildMessage($notificationEvent, $customer['name'] ?? '', $customer['email'] ?? '', $customer['phone'] ?? '');
        $mandatory = in_array($notificationEvent->category(), config('afprospos.notifications.mandatory_categories', []), true);
        $emailEnabled = $mandatory || (bool) ($customer['notification_preferences']['email'] ?? true);

        if ($emailEnabled) {
            $this->sendEmail($notificationEventId, $attemptNumber, $message);
        }

        // In-app is always recorded regardless of preference — NOTIF-BR-18.
        $this->record($notificationEventId, 'in_app', 'in_app', $attemptNumber, $this->inApp->send($message));
    }

    private function processForStaff(NotificationEvent $notificationEvent, int $notificationEventId, int $attemptNumber): void
    {
        $staff = $this->staff->find($notificationEvent->recipientId());

        if ($staff === null) {
            $this->record($notificationEventId, 'in_app', 'none', $attemptNumber, DeliveryResult::failedPermanent('none', 'Recipient staff member no longer exists.'));

            return;
        }

        $message = $this->buildMessage($notificationEvent, $staff['name'] ?? '', $staff['email'] ?? '', '');

        $this->sendEmail($notificationEventId, $attemptNumber, $message);

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

    private function buildMessage(NotificationEvent $notificationEvent, string $recipientName, string $recipientEmail, string $recipientPhone): NotificationMessage
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
            recipientEmail: $recipientEmail,
            recipientPhone: $recipientPhone,
            recipientName: $recipientName,
            title: $title,
            body: $body,
            actionUrl: $this->buildActionUrl($notificationEvent),
        );
    }

    private function buildActionUrl(NotificationEvent $notificationEvent): string
    {
        $template = self::ACTION_URL_TEMPLATES[$notificationEvent->eventType()] ?? null;

        if ($template === null) {
            return '';
        }

        $path = str_contains($template, '%d') ? sprintf($template, $notificationEvent->sourceId()) : $template;

        return rtrim((string) config('app.url'), '/').$path;
    }
}
