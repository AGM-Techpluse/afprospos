<?php

declare(strict_types=1);

namespace Domain\Payments\Application\DTOs;

final readonly class PaymentGatewayRefundResult
{
    public function __construct(
        public ?string $providerReference = null,
    ) {}
}
