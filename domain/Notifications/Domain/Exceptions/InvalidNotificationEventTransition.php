<?php

declare(strict_types=1);

namespace Domain\Notifications\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

final class InvalidNotificationEventTransition extends DomainException
{
    public static function forNotificationEvent(int $notificationEventId, string $from, string $to): self
    {
        return new self("Notification event [{$notificationEventId}] cannot transition from [{$from}] to [{$to}].");
    }
}
