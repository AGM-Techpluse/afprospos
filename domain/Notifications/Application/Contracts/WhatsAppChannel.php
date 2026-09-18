<?php

declare(strict_types=1);

namespace Domain\Notifications\Application\Contracts;

use Domain\Notifications\Application\DTOs\DeliveryResult;
use Domain\Notifications\Application\DTOs\NotificationMessage;

/** No WhatsApp provider is selected yet (ADD §21) — this contract exists so the channel abstraction is real and swapping in a provider later is a new Infrastructure class, not a NotificationEngine rewrite. */
interface WhatsAppChannel
{
    public function send(NotificationMessage $message): DeliveryResult;

    public function isConfigured(): bool;
}
