<?php

declare(strict_types=1);

namespace Domain\Payments\Application\DTOs;

/** What a `PaymentGateway` needs to initiate a capture — deliberately narrower than `InitiatePaymentCommand` (no payable/actor identifiers, a gateway only cares about the money and the method). */
final readonly class InitiatePaymentGatewayRequest
{
    public function __construct(
        public string $method,
        public int $amountMinor,
        public ?string $providerReference = null,
    ) {}
}
