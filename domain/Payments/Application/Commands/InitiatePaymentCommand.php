<?php

declare(strict_types=1);

namespace Domain\Payments\Application\Commands;

/**
 * The single payment-initiation contract other modules (Sales, and later
 * Repair) call through `Domain\Payments\Application\Contracts\PaymentInitiationService`
 * — mirrors `ReserveInventoryCommand`'s role as the shared cross-module
 * write contract's parameter type.
 */
final readonly class InitiatePaymentCommand
{
    public function __construct(
        public string $payableType,
        public int $payableId,
        public string $method,
        public int $amountMinor,
        public int $initiatedByStaffId,
        public ?string $providerReference = null,
    ) {}
}
