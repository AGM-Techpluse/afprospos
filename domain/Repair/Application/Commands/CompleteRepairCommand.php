<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

/** REP-BR-27: no financial status here — see RequestRepairPaymentApprovalCommand/DecideRepairPaymentApprovalCommand for how financial_status actually changes after completion. */
final readonly class CompleteRepairCommand
{
    public function __construct(
        public int $repairJobId,
        public ?string $resolutionNotes,
        /** @var array<int, array{diagnosis_case_fault_id?: int|null, device_problem_tag_id?: int|null, label: string, notes?: string|null, commission_amount_minor?: int|null}> */
        public array $fixedFaults,
        public int $completedByStaffId,
    ) {}
}
