<?php

declare(strict_types=1);

namespace Domain\Notifications\Infrastructure\Channels\Sms;

use Domain\Notifications\Application\Contracts\SmsChannel;
use Domain\Notifications\Application\DTOs\DeliveryResult;
use Domain\Notifications\Application\DTOs\NotificationMessage;

/** No SMS provider is selected yet — see UnconfiguredWhatsAppChannel's docblock, same reasoning. */
final class UnconfiguredSmsChannel implements SmsChannel
{
    public function isConfigured(): bool
    {
        return false;
    }

    public function send(NotificationMessage $message): DeliveryResult
    {
        return DeliveryResult::failedPermanent('none', 'No SMS provider configured.');
    }
}
