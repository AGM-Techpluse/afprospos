<?php

declare(strict_types=1);

namespace Domain\Notifications\Infrastructure\Channels\Email;

use Illuminate\Mail\Mailable;

/** Shared by both ResendMailer and SmtpMailer — a plain text body is enough for this slice; a styled HTML template is a fast-follow, not a functional requirement. */
final class NotificationMailable extends Mailable
{
    public function __construct(
        private readonly string $title,
        private readonly string $body,
    ) {}

    public function build(): self
    {
        return $this->subject($this->title)->text('notifications.email-plain', [
            'title' => $this->title,
            'body' => $this->body,
        ]);
    }
}
