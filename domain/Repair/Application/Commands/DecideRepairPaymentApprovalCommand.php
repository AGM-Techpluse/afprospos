<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

/** REP-BR-28: the Shop Owner's decision — approving records the requested financial status; rejecting leaves it untouched. */
final readonly class DecideRepairPaymentApprovalCommand
{
    public function __construct(
        public int $approvalId,
        public bool $approved,
        public int $decidedByStaffId,
    ) {}
}
