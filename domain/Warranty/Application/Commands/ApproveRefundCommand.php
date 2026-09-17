<?php

declare(strict_types=1);

namespace Domain\Warranty\Application\Commands;

/** WAR-BR-06's separation of duties: gated behind `warranty.approve-refund`, distinct from `warranty.resolve` (repair/replace execution). */
final readonly class ApproveRefundCommand
{
    public function __construct(
        public int $warrantyClaimId,
        public int $paymentTransactionId,
        public int $approvedByStaffId,
    ) {}
}
