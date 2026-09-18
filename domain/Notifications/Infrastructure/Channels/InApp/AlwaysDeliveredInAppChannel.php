<?php

declare(strict_types=1);

namespace Domain\Notifications\Infrastructure\Channels\InApp;

use Domain\Notifications\Application\Contracts\InAppChannel;
use Domain\Notifications\Application\DTOs\DeliveryResult;
use Domain\Notifications\Application\DTOs\NotificationMessage;

/** NOTIF-BR-18: the NotificationEvent row's own existence is the delivery — there is nothing external to call. */
final class AlwaysDeliveredInAppChannel implements InAppChannel
{
    public function send(NotificationMessage $message): DeliveryResult
    {
        return DeliveryResult::delivered('in_app');
    }
}
