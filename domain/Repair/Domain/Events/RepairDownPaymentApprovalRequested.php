<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Events;

use Carbon\CarbonImmutable;

/** Frontdesk has recorded that the customer paid the down payment — needs Manager/Shop Owner approval before the repair is authorized to proceed. */
final readonly class RepairDownPaymentApprovalRequested
{
    public function __construct(
        public int $repairJobId,
        public int $shopId,
        public int $requestedByStaffId,
        public CarbonImmutable $occurredAt,
    ) {}
}
