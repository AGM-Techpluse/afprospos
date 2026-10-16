<?php

declare(strict_types=1);

namespace Domain\Payments\Application\Commands;

/** `amountMinor` null means "refund whatever remains outstanding" — preserves full-refund-only behavior for callers (Warranty, Returns) that don't collect a partial amount. */
final readonly class RefundPaymentCommand
{
    public function __construct(
        public int $transactionId,
        public int $refundedByStaffId,
        public ?int $amountMinor = null,
    ) {}
}
