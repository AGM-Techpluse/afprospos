<?php

declare(strict_types=1);

namespace Domain\Notifications\Application\DTOs;

/** What a channel actually sends — resolved by NotificationEngine from a NotificationEvent + the recipient's contact info, so a channel implementation never has to look anything up itself. */
final readonly class NotificationMessage
{
    public function __construct(
        public int $notificationEventId,
        public string $recipientEmail,
        public string $recipientPhone,
        public string $recipientName,
        public string $title,
        public string $body,
    ) {}
}
