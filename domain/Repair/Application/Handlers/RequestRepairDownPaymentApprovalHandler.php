<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Carbon\CarbonImmutable;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Repair\Application\Commands\RequestRepairDownPaymentApprovalCommand;
use Domain\Repair\Domain\Entities\RepairDownPaymentApproval;
use Domain\Repair\Domain\Events\RepairDownPaymentApprovalRequested;
use Domain\Repair\Domain\Repositories\RepairDownPaymentApprovalRepository;
use Domain\Repair\Domain\Repositories\RepairJobRepository;
use Domain\Repair\Domain\ValueObjects\RepairJobId;
use Domain\Shared\Domain\ValueObjects\Money;
use Domain\Shared\Domain\ValueObjects\StaffId;
use Illuminate\Support\Facades\Event;

final class RequestRepairDownPaymentApprovalHandler
{
    public function __construct(
        private readonly RepairJobRepository $repairJobs,
        private readonly RepairDownPaymentApprovalRepository $approvals,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(RequestRepairDownPaymentApprovalCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $jobId = new RepairJobId($command->repairJobId);
            $job = $this->repairJobs->lockForUpdate($jobId);
            $job->assertAwaitingDownPayment();

            $approval = RepairDownPaymentApproval::request(
                $jobId,
                new Money($command->amountMinor),
                $command->method,
                $command->requestedByStaffId,
            );
            $id = $this->approvals->save($approval);

            $this->audit->record(
                module: 'Repair',
                eventType: 'RepairDownPaymentApprovalRequested',
                actorStaffId: new StaffId($command->requestedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'repair_down_payment_approval',
                subjectId: $id->value,
                beforeState: null,
                afterState: ['amount_minor' => $command->amountMinor, 'method' => $command->method],
            );

            Event::dispatch(new RepairDownPaymentApprovalRequested($jobId->value, $job->shopId()->value, $command->requestedByStaffId, CarbonImmutable::now()));
        });
    }
}
