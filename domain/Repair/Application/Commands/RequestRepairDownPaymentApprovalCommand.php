<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

/** Frontdesk requests that the down payment they just received from the customer be recorded — requires Manager/Shop Owner approval before it takes effect. */
final readonly class RequestRepairDownPaymentApprovalCommand
{
    /** @param  'cash'|'pos_terminal'|'bank_transfer'  $method */
    public function __construct(
        public int $repairJobId,
        public int $amountMinor,
        public string $method,
        public int $requestedByStaffId,
    ) {}
}
