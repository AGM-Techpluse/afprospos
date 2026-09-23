<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Carbon\CarbonImmutable;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Payments\Application\Commands\InitiatePaymentCommand;
use Domain\Payments\Application\Contracts\PaymentInitiationService;
use Domain\Repair\Application\Commands\DecideRepairDownPaymentApprovalCommand;
use Domain\Repair\Domain\Repositories\RepairDownPaymentApprovalRepository;
use Domain\Repair\Domain\Repositories\RepairJobRepository;
use Domain\Repair\Domain\ValueObjects\RepairDownPaymentApprovalId;
use Domain\Shared\Domain\ValueObjects\StaffId;

/**
 * Approval is what authorizes `RepairJob::grantAuthorization()` and
 * initiates the real `payments_transactions` row (purpose
 * `repair_down_payment`) — previously `authorize()` created and
 * auto-confirmed that transaction itself the moment the Manager set the
 * amount, with no step for frontdesk to actually record having taken the
 * customer's money. Mirrors `DecideRepairPaymentApprovalHandler` exactly.
 */
final class DecideRepairDownPaymentApprovalHandler
{
    public function __construct(
        private readonly RepairDownPaymentApprovalRepository $approvals,
        private readonly RepairJobRepository $repairJobs,
        private readonly PaymentInitiationService $payments,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(DecideRepairDownPaymentApprovalCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $id = new RepairDownPaymentApprovalId($command->approvalId);
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
                $job->grantAuthorization();
                $this->repairJobs->save($job);

                $this->payments->initiate(new InitiatePaymentCommand(
                    payableType: 'repair_job',
                    payableId: $approval->repairJobId()->value,
                    purpose: 'repair_down_payment',
                    method: $approval->method(),
                    amountMinor: $approval->amount()->minor,
                    initiatedByStaffId: $command->decidedByStaffId,
                ));
            }

            $this->audit->record(
                module: 'Repair',
                eventType: $command->approved ? 'RepairDownPaymentApproved' : 'RepairDownPaymentRejected',
                actorStaffId: new StaffId($command->decidedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'repair_down_payment_approval',
                subjectId: $id->value,
                beforeState: null,
                afterState: ['status' => $approval->status()],
            );
        });
    }
}
