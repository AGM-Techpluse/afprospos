<?php

declare(strict_types=1);

namespace Domain\Payments\Application\DTOs;

final readonly class RefundPaymentGatewayRequest
{
    public function __construct(
        public string $method,
        public int $amountMinor,
        public ?string $providerReference = null,
    ) {}
}
