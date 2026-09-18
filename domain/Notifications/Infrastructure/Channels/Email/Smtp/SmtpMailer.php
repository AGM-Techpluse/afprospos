<?php

declare(strict_types=1);

namespace Domain\Notifications\Infrastructure\Channels\Email\Smtp;

use Domain\Notifications\Application\Contracts\EmailChannel;
use Domain\Notifications\Application\DTOs\DeliveryResult;
use Domain\Notifications\Application\DTOs\NotificationMessage;
use Domain\Notifications\Infrastructure\Channels\Email\NotificationMailable;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * The NOTIF-BR-08 failover target when Resend isn't configured or fails —
 * sends through the app's own `MAIL_MAILER`-configured mailer (`smtp` in
 * production, `log` in local dev by Laravel's own default), so a
 * notification is always attempted through *some* real transport rather
 * than requiring SMTP specifically to be set up before anything works.
 * Always "configured" for that reason — the log driver never errors.
 */
final class SmtpMailer implements EmailChannel
{
    public function isConfigured(): bool
    {
        return true;
    }

    public function send(NotificationMessage $message): DeliveryResult
    {
        if ($message->recipientEmail === '') {
            return DeliveryResult::failedPermanent('smtp', 'Recipient has no email address on file.');
        }

        try {
            Mail::to($message->recipientEmail)->send(new NotificationMailable($message->title, $message->body));

            return DeliveryResult::delivered(config('mail.default', 'smtp'));
        } catch (Throwable $exception) {
            return DeliveryResult::failedTransient(config('mail.default', 'smtp'), $exception->getMessage());
        }
    }
}
