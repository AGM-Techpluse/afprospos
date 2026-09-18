<?php

declare(strict_types=1);

namespace Domain\Warranty\Application\Commands;

/** Separate from approve/deny — same separation-of-duties pattern as Claims' ApproveRefundCommand (WAR-BR-06), gated behind `warranty.approve-refund` at the route level. */
final readonly class ProcessReturnRefundCommand
{
    public function __construct(
        public int $returnRequestId,
        public int $paymentTransactionId,
        public int $processedByStaffId,
    ) {}
}
