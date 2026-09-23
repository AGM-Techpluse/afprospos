<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

/** REP-BR-28: frontdesk requests that a completed repair's payment be recorded — requires Shop Owner approval before it takes effect. */
final readonly class RequestRepairPaymentApprovalCommand
{
    /**
     * @param  'partially_paid'|'fully_paid'  $requestedFinancialStatus
     * @param  'cash'|'pos_terminal'|'bank_transfer'  $method
     */
    public function __construct(
        public int $repairJobId,
        public string $requestedFinancialStatus,
        public int $amountMinor,
        public string $method,
        public int $requestedByStaffId,
    ) {}
}
