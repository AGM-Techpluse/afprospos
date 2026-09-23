<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Carbon\CarbonImmutable;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Payments\Application\Commands\InitiatePaymentCommand;
use Domain\Payments\Application\Contracts\PaymentInitiationService;
use Domain\Repair\Application\Commands\DecideRepairPaymentApprovalCommand;
use Domain\Repair\Domain\Repositories\RepairJobRepository;
use Domain\Repair\Domain\Repositories\RepairPaymentApprovalRepository;
use Domain\Repair\Domain\ValueObjects\RepairPaymentApprovalId;
use Domain\Shared\Domain\ValueObjects\StaffId;

/**
 * REP-BR-28: approval is the act that authorizes `RepairJob::recordFinancialStatus()`
 * — gated behind `repairs.approve-payment` at the route level, distinct from
 * `repairs.edit` (requesting) and `repairs.approve` (pre-work authorization).
 * Approval also initiates the real `payments_transactions` row (purpose
 * `repair_final_payment`) — previously the completion payment was never
 * recorded as a transaction at all, only the intake down payment was.
 */
final class DecideRepairPaymentApprovalHandler
{
    public function __construct(
        private readonly RepairPaymentApprovalRepository $approvals,
        private readonly RepairJobRepository $repairJobs,
        private readonly PaymentInitiationService $payments,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(DecideRepairPaymentApprovalCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $id = new RepairPaymentApprovalId($command->approvalId);
            $approval = $this->approvals->lockForUpdate($id);
            $now = CarbonImmutable::now();

            if ($command->approved) {
                $approval->approve($command->decidedByStaffId, $now);
            } else {
                $approval->reject($command->decidedByStaffId, $now);
            }

            $this->approvals->save($approval);

            if ($command->approved) {
                $job = $this->repairJobs->lockForUpdate($approval->repairJobId());
                $job->recordFinancialStatus($approval->requestedFinancialStatus());
                $this->repairJobs->save($job);

                $this->payments->initiate(new InitiatePaymentCommand(
                    payableType: 'repair_job',
                    payableId: $approval->repairJobId()->value,
                    purpose: 'repair_final_payment',
                    method: $approval->method(),
                    amountMinor: $approval->amount()->minor,
                    initiatedByStaffId: $command->decidedByStaffId,
                ));
            }

            $this->audit->record(
                module: 'Repair',
                eventType: $command->approved ? 'RepairPaymentApproved' : 'RepairPaymentRejected',
                actorStaffId: new StaffId($command->decidedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'repair_payment_approval',
                subjectId: $id->value,
                beforeState: null,
                afterState: ['status' => $approval->status()],
            );
        });
    }
}
