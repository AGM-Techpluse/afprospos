<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Carbon\CarbonImmutable;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Payments\Application\Commands\InitiatePaymentCommand;
use Domain\Payments\Application\Contracts\PaymentInitiationService;
use Domain\Payments\Application\Contracts\PaymentTransactionLookup;
use Domain\Repair\Application\Commands\DecideRepairPaymentApprovalCommand;
use Domain\Repair\Domain\Repositories\RepairDiagnosisFeeRepository;
use Domain\Repair\Domain\Repositories\RepairJobRepository;
use Domain\Repair\Domain\Repositories\RepairPaymentApprovalRepository;
use Domain\Repair\Domain\ValueObjects\RepairJobId;
use Domain\Repair\Domain\ValueObjects\RepairPaymentApprovalId;
use Domain\Shared\Domain\ValueObjects\StaffId;

/**
 * REP-BR-28: approval is the act that authorizes `RepairJob::recordFinancialStatus()`
 * — gated behind `repairs.approve-payment` at the route level, distinct from
 * `repairs.edit` (requesting) and `repairs.approve` (pre-work authorization).
 * Approval also initiates the real `payments_transactions` row(s) for the
 * approved amount.
 */
final class DecideRepairPaymentApprovalHandler
{
    public function __construct(
        private readonly RepairPaymentApprovalRepository $approvals,
        private readonly RepairJobRepository $repairJobs,
        private readonly RepairDiagnosisFeeRepository $diagnosisFees,
        private readonly PaymentTransactionLookup $paymentLookup,
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

                // A `fully_paid` approval's amount is validated at request time
                // (RequestRepairPaymentApprovalHandler::expectedOutstandingMinor())
                // to already include any outstanding diagnosis fee folded into the
                // quote — split that portion into its own repair_diagnosis_fee
                // transaction so RepairJobDetailQuery::diagnosisFee()['paid'] isn't
                // left permanently false despite the money having been collected.
                // Down-payment amounts aren't validated against the fee the same
                // way, so this split is only safe for fully_paid approvals.
                $diagnosisFeeMinor = $approval->requestedFinancialStatus() === 'fully_paid'
                    ? $this->outstandingDiagnosisFeeMinor($approval->repairJobId())
                    : 0;

                if ($diagnosisFeeMinor > 0) {
                    $this->payments->initiate(new InitiatePaymentCommand(
                        payableType: 'repair_job',
                        payableId: $approval->repairJobId()->value,
                        purpose: 'repair_diagnosis_fee',
                        method: $approval->method(),
                        amountMinor: $diagnosisFeeMinor,
                        initiatedByStaffId: $command->decidedByStaffId,
                    ));
                }

                $remainderMinor = $approval->amount()->minor - $diagnosisFeeMinor;

                if ($remainderMinor > 0) {
                    $this->payments->initiate(new InitiatePaymentCommand(
                        payableType: 'repair_job',
                        payableId: $approval->repairJobId()->value,
                        purpose: 'repair_final_payment',
                        method: $approval->method(),
                        amountMinor: $remainderMinor,
                        initiatedByStaffId: $command->decidedByStaffId,
                    ));
                }
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

    /**
     * Mirrors RequestRepairPaymentApprovalHandler::expectedOutstandingMinor()'s
     * diagnosis-fee check. Also checks the fee collected against the
     * originating diagnosis case (before this repair job existed) so a
     * pre-paid fee correctly reports 0 outstanding and never gets folded
     * into (double-counted in) this fully_paid approval.
     */
    private function outstandingDiagnosisFeeMinor(RepairJobId $repairJobId): int
    {
        $diagnosisFee = $this->diagnosisFees->find($repairJobId);

        if ($diagnosisFee === null || $diagnosisFee->decision() !== 'approved') {
            return 0;
        }

        $transaction = $this->paymentLookup->latestTransactionForPurpose('repair_job', $repairJobId->value, 'repair_diagnosis_fee');

        if (($transaction['status'] ?? null) === 'confirmed') {
            return 0;
        }

        $diagnosisCaseId = $this->repairJobs->findDiagnosisCaseId($repairJobId);

        if ($diagnosisCaseId !== null) {
            $caseTransaction = $this->paymentLookup->latestTransactionForPurpose('diagnosis_case', $diagnosisCaseId, 'repair_diagnosis_fee');

            if (($caseTransaction['status'] ?? null) === 'confirmed') {
                return 0;
            }
        }

        return $diagnosisFee->amountMinor();
    }
}
