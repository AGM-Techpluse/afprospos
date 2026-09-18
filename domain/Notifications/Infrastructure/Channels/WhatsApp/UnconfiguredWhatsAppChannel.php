<?php

declare(strict_types=1);

namespace Domain\Notifications\Infrastructure\Channels\WhatsApp;

use Domain\Notifications\Application\Contracts\WhatsAppChannel;
use Domain\Notifications\Application\DTOs\DeliveryResult;
use Domain\Notifications\Application\DTOs\NotificationMessage;

/** No WhatsApp provider is selected yet — see the Phase 8 slice-1 plan's "Channel scope" decision. Structurally real (implements the real interface, is bound in the ServiceProvider) so swapping in a provider later is a new class, not a NotificationEngine rewrite. */
final class UnconfiguredWhatsAppChannel implements WhatsAppChannel
{
    public function isConfigured(): bool
    {
        return false;
    }

    public function send(NotificationMessage $message): DeliveryResult
    {
        return DeliveryResult::failedPermanent('none', 'No WhatsApp provider configured.');
    }
}
