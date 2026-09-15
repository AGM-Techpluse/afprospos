<?php

declare(strict_types=1);

namespace Domain\Payments\Application\DTOs;

/** `$status` is 'pending' or 'confirmed' — cash/POS-terminal gateways return 'confirmed' immediately; bank-transfer returns 'pending'. */
final readonly class PaymentGatewayInitiationResult
{
    public function __construct(
        public string $status,
        public ?string $providerReference = null,
    ) {}
}
