<?php

declare(strict_types=1);

namespace Domain\Notifications\Domain\Exceptions;

use Domain\Shared\Domain\Exceptions\DomainException;

final class NotificationDoesNotBelongToCustomer extends DomainException
{
    public static function forNotificationEvent(int $notificationEventId): self
    {
        return new self("Notification event [{$notificationEventId}] does not belong to this customer.");
    }
}
