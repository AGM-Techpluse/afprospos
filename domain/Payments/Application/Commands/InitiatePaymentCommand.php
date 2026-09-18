<?php

declare(strict_types=1);

namespace Domain\Payments\Application\Commands;

/**
 * The single payment-initiation contract other modules (Sales, and later
 * Repair) call through `Domain\Payments\Application\Contracts\PaymentInitiationService`
 * — mirrors `ReserveInventoryCommand`'s role as the shared cross-module
 * write contract's parameter type.
 *
 * `initiatedByStaffId`/`initiatedByCustomerId` mirror `CreateWarrantyClaimCommand`'s
 * dual-actor shape: exactly one is set. A customer-initiated payment (bank
 * transfer only today) always resolves to the gateway's `pending` branch,
 * which never needs a staff id — only the synchronous cash/POS `confirmed`
 * branch does.
 */
final readonly class InitiatePaymentCommand
{
    public function __construct(
        public string $payableType,
        public int $payableId,
        public string $method,
        public int $amountMinor,
        public ?int $initiatedByStaffId = null,
        public ?int $initiatedByCustomerId = null,
        public ?string $providerReference = null,
    ) {}
}
