<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Carbon\CarbonImmutable;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Payments\Application\Contracts\PaymentTransactionLookup;
use Domain\Repair\Application\Commands\RequestRepairPaymentApprovalCommand;
use Domain\Repair\Domain\Entities\RepairJob;
use Domain\Repair\Domain\Entities\RepairPaymentApproval;
use Domain\Repair\Domain\Events\RepairPaymentApprovalRequested;
use Domain\Repair\Domain\Repositories\RepairDiagnosisFeeRepository;
use Domain\Repair\Domain\Repositories\RepairJobRepository;
use Domain\Repair\Domain\Repositories\RepairPaymentApprovalRepository;
use Domain\Repair\Domain\ValueObjects\RepairJobId;
use Domain\Shared\Domain\ValueObjects\Money;
use Domain\Shared\Domain\ValueObjects\StaffId;
use Illuminate\Support\Facades\Event;

final class RequestRepairPaymentApprovalHandler
{
    public function __construct(
        private readonly RepairJobRepository $repairJobs,
        private readonly RepairPaymentApprovalRepository $approvals,
        private readonly RepairDiagnosisFeeRepository $diagnosisFees,
        private readonly PaymentTransactionLookup $payments,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(RequestRepairPaymentApprovalCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $jobId = new RepairJobId($command->repairJobId);
            $job = $this->repairJobs->lockForUpdate($jobId);
            $job->assertCompleted();
            $expectedOutstandingMinor = $this->expectedOutstandingMinor($job, $jobId);

            if ($command->requestedFinancialStatus === 'fully_paid' && $command->amountMinor !== $expectedOutstandingMinor) {
                throw new \InvalidArgumentException('Full payment must match the outstanding repair balance.');
            }

            $approval = RepairPaymentApproval::request(
                $jobId,
                $command->requestedFinancialStatus,
                new Money($command->amountMinor),
                $command->method,
                $command->requestedByStaffId,
            );
            $id = $this->approvals->save($approval);

            $this->audit->record(
                module: 'Repair',
                eventType: 'RepairPaymentApprovalRequested',
                actorStaffId: new StaffId($command->requestedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'repair_payment_approval',
                subjectId: $id->value,
                beforeState: null,
                afterState: ['requested_financial_status' => $command->requestedFinancialStatus, 'amount_minor' => $command->amountMinor],
            );

            Event::dispatch(new RepairPaymentApprovalRequested($jobId->value, $job->shopId()->value, $command->requestedByStaffId, CarbonImmutable::now()));
        });
    }

    private function expectedOutstandingMinor(RepairJob $job, RepairJobId $jobId): int
    {
        $quoteTotalMinor = $job->repairCost()?->minor ?? 0;
        $diagnosisFee = $this->diagnosisFees->find($jobId);
        $diagnosisTransaction = $this->payments->latestTransactionForPurpose('repair_job', $jobId->value, 'repair_diagnosis_fee');
        $diagnosisFeePaid = ($diagnosisTransaction['status'] ?? null) === 'confirmed';

        if (! $diagnosisFeePaid) {
            $diagnosisCaseId = $this->repairJobs->findDiagnosisCaseId($jobId);

            if ($diagnosisCaseId !== null) {
                $caseTransaction = $this->payments->latestTransactionForPurpose('diagnosis_case', $diagnosisCaseId, 'repair_diagnosis_fee');
                $diagnosisFeePaid = ($caseTransaction['status'] ?? null) === 'confirmed';
            }
        }

        if ($diagnosisFee !== null && $diagnosisFee->decision() === 'approved' && ! $diagnosisFeePaid) {
            $quoteTotalMinor += $diagnosisFee->amountMinor();
        }

        $downPayment = $this->payments->latestTransactionForPurpose('repair_job', $jobId->value, 'repair_down_payment');
        $finalPayment = $this->payments->latestTransactionForPurpose('repair_job', $jobId->value, 'repair_final_payment');
        $paidMinor = 0;

        if (($downPayment['status'] ?? null) === 'confirmed') {
            $paidMinor += $downPayment['amount_minor'];
        }

        if (($finalPayment['status'] ?? null) === 'confirmed') {
            $paidMinor += $finalPayment['amount_minor'];
        }

        return max(0, $quoteTotalMinor - $paidMinor);
    }
}
