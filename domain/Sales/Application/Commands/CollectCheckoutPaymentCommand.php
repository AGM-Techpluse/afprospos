<?php

declare(strict_types=1);

namespace Domain\Sales\Application\Commands;

/**
 * Collects one or more payment legs against an open checkout, then
 * completes the sale only once the running total (this submission plus
 * anything already confirmed earlier) reaches the checkout's total — the
 * same mechanism covers a split payment (2+ legs, paid together, reaching
 * the full amount at once) and a partial payment (1 leg, less than the
 * full amount, the checkout staying open for a later submission to
 * finish it) without being two different code paths.
 *
 * A customer-submitted bank transfer being confirmed by staff is a
 * separate, already-working entry point (PaymentController::confirm(),
 * CreateSaleFromPaidCheckoutHandler's own `existingPaymentTransactionId`
 * path) — deliberately untouched and not routed through this Handler.
 */
final readonly class CollectCheckoutPaymentCommand
{
    /** @param  array<int, array{method: string, amount_minor: int, reference: ?string}>  $payments */
    public function __construct(
        public int $checkoutId,
        public array $payments,
        public int $confirmedByStaffId,
        public string $shopCode,
        /**
         * A customer-submitted bank transfer already pending confirmation
         * for this checkout (RequestCheckoutBankTransferCommand) — staff
         * confirms and counts that existing transaction toward the running
         * total instead of it being invisible to this submission, same as
         * CreateSaleFromPaidCheckoutCommand's own field of this name.
         */
        public ?int $existingPaymentTransactionId = null,
    ) {}
}
