<?php

declare(strict_types=1);

namespace Domain\Notifications\Application\Commands;

final readonly class MarkNotificationReadCommand
{
    public function __construct(
        public int $notificationEventId,
        public int $customerId,
    ) {}
}
