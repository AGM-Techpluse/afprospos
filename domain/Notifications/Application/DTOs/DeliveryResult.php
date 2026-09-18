<?php

declare(strict_types=1);

namespace Domain\Notifications\Application\DTOs;

/** NOTIF-BR-07's failure classification, returned by every channel so NotificationEngine can decide retry vs. exhausted without knowing which provider produced the result. */
final readonly class DeliveryResult
{
    private function __construct(
        public string $provider,
        public string $status,
        public ?string $providerResponse,
    ) {}

    public static function delivered(string $provider, ?string $providerResponse = null): self
    {
        return new self($provider, 'delivered', $providerResponse);
    }

    public static function failedTransient(string $provider, ?string $providerResponse): self
    {
        return new self($provider, 'failed_transient', $providerResponse);
    }

    public static function failedPermanent(string $provider, ?string $providerResponse): self
    {
        return new self($provider, 'failed_permanent', $providerResponse);
    }

    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }

    public function isTransient(): bool
    {
        return $this->status === 'failed_transient';
    }
}
