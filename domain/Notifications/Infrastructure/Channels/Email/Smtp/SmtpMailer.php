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
 * The NOTIF-BR-08 failover target — sends through the app's `MAIL_MAILER`
 * transport (smtp, ses, postmark, …). The `log` and `array` drivers do not
 * deliver anything, so they count as "not configured": reporting them as
 * delivered would show staff a success for an email that never left the server.
 */
final class SmtpMailer implements EmailChannel
{
    /** Mailers that write to a log or memory instead of a real transport. */
    private const NON_DELIVERING_MAILERS = ['log', 'array'];

    public function isConfigured(): bool
    {
        return ! in_array((string) config('mail.default'), self::NON_DELIVERING_MAILERS, true);
    }

    public function send(NotificationMessage $message): DeliveryResult
    {
        $mailer = (string) config('mail.default');

        if ($message->recipientEmail === '') {
            return DeliveryResult::failedPermanent($mailer, 'Recipient has no email address on file.');
        }

        if (! $this->isConfigured()) {
            return DeliveryResult::failedPermanent($mailer, "MAIL_MAILER is [{$mailer}], which does not deliver mail. Nothing was sent.");
        }

        try {
            Mail::to($message->recipientEmail)->send(new NotificationMailable($message->title, $message->body, $message->actionUrl));

            return DeliveryResult::delivered($mailer);
        } catch (Throwable $exception) {
            return DeliveryResult::failedTransient($mailer, $exception->getMessage());
        }
    }
}
