<?php

declare(strict_types=1);

namespace Domain\Sales\Application\Commands;

/**
 * Converts an open checkout into a finalized sale (BLD SALE/INV-BR-05).
 * Covers both Phase 4 completion paths with the same command: a
 * cashier completing an immediate cash/terminal sale, and staff later
 * confirming a remote/bank-transfer checkout — the only difference is
 * timing, not shape (Implementation Plan item 4's cash/terminal/
 * bank-transfer "capture at the boundary").
 *
 * `existingPaymentTransactionId`: when a customer already submitted a
 * bank-transfer payment for this checkout (`RequestCheckoutBankTransferCommand`),
 * staff confirms and reuses that transaction instead of initiating a new
 * one, so one checkout never produces two payment rows. Null preserves
 * today's behavior exactly (staff picks a method fresh, a new transaction
 * is initiated).
 */
final readonly class CreateSaleFromPaidCheckoutCommand
{
    public function __construct(
        public int $checkoutId,
        public string $paymentMethod,
        public ?string $paymentReference,
        public int $confirmedByStaffId,
        public string $shopCode,
        public ?int $existingPaymentTransactionId = null,
    ) {}
}
