<?php

declare(strict_types=1);

namespace Domain\Payments\Application\Commands;

/** Full-refund only — the DBDD schema has no partial-refund amount field; that's a Phase 7/Warranty concern. */
final readonly class RefundPaymentCommand
{
    public function __construct(
        public int $transactionId,
        public int $refundedByStaffId,
    ) {}
}
