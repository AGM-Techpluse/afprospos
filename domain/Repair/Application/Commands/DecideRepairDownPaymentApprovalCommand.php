<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

final readonly class DecideRepairDownPaymentApprovalCommand
{
    public function __construct(
        public int $approvalId,
        public bool $approved,
        public int $decidedByStaffId,
    ) {}
}
