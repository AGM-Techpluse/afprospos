<?php

declare(strict_types=1);

namespace Domain\Notifications\Infrastructure\Channels\Email\Resend;

use Domain\Notifications\Application\Contracts\EmailChannel;
use Domain\Notifications\Application\DTOs\DeliveryResult;
use Domain\Notifications\Application\DTOs\NotificationMessage;
use Domain\Notifications\Infrastructure\Channels\Email\NotificationMailable;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** ADD §21A: tried first when configured — NotificationEngine falls back to SmtpMailer otherwise (NOTIF-BR-08). */
final class ResendMailer implements EmailChannel
{
    public function isConfigured(): bool
    {
        return filled(config('services.resend.key'));
    }

    public function send(NotificationMessage $message): DeliveryResult
    {
        if ($message->recipientEmail === '') {
            return DeliveryResult::failedPermanent('resend', 'Recipient has no email address on file.');
        }

        try {
            Mail::mailer('resend')->to($message->recipientEmail)->send(new NotificationMailable($message->title, $message->body));

            return DeliveryResult::delivered('resend');
        } catch (Throwable $exception) {
            // A provider/transport failure is treated as transient — NOTIF-BR-07 — so
            // NotificationEngine's SMTP fallback and the job's own retry both get a chance.
            return DeliveryResult::failedTransient('resend', $exception->getMessage());
        }
    }
}
