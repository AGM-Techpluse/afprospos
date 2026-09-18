<?php

declare(strict_types=1);

namespace Domain\Notifications\Application\Commands;

final readonly class RecordDeliveryAttemptCommand
{
    public function __construct(
        public int $notificationEventId,
        public string $channel,
        public string $provider,
        public int $attemptNumber,
        public string $status,
        public ?string $providerResponse,
    ) {}
}
