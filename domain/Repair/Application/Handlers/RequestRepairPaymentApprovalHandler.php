<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Carbon\CarbonImmutable;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Repair\Application\Commands\RequestRepairPaymentApprovalCommand;
use Domain\Repair\Domain\Entities\RepairPaymentApproval;
use Domain\Repair\Domain\Events\RepairPaymentApprovalRequested;
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
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(RequestRepairPaymentApprovalCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $jobId = new RepairJobId($command->repairJobId);
            $job = $this->repairJobs->lockForUpdate($jobId);
            $job->assertCompleted();

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
}
