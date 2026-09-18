<?php

declare(strict_types=1);

namespace Domain\Notifications\Application\Contracts;

use Domain\Notifications\Application\DTOs\DeliveryResult;
use Domain\Notifications\Application\DTOs\NotificationMessage;

/** NOTIF-BR-18: in-app is "an internal, always-available notification record rather than an external delivery channel subject to the same failure modes" — the NotificationEvent row's own existence is the delivery. */
interface InAppChannel
{
    public function send(NotificationMessage $message): DeliveryResult;
}
