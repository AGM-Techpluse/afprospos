<?php

declare(strict_types=1);

namespace Domain\Notifications\Application\Contracts;

use Domain\Notifications\Application\DTOs\DeliveryResult;
use Domain\Notifications\Application\DTOs\NotificationMessage;

/** No SMS provider is selected yet (ADD §21) — see WhatsAppChannel's docblock, same reasoning. */
interface SmsChannel
{
    public function send(NotificationMessage $message): DeliveryResult;

    public function isConfigured(): bool;
}
