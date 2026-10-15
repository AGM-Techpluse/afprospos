<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Repair\Application\Commands\CancelRepairCommand;
use Domain\Repair\Application\Services\ReleaseRepairPartsService;
use Domain\Repair\Domain\Repositories\RepairJobRepository;
use Domain\Repair\Domain\ValueObjects\RepairJobId;
use Domain\Shared\Domain\ValueObjects\StaffId;

/** Cancels an open repair (the customer declined the quote, or staff stopped the job) and releases any parts it had reserved. */
final class CancelRepairHandler
{
    public function __construct(
        private readonly RepairJobRepository $repairJobs,
        private readonly ReleaseRepairPartsService $releaseParts,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(CancelRepairCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $jobId = new RepairJobId($command->repairJobId);
            $job = $this->repairJobs->lockForUpdate($jobId);
            $previous = $job->status();

            $job->cancel($command->reason);
            $this->releaseParts->releaseOutstanding($jobId, $job->shopId()->value);
            $this->repairJobs->save($job);

            $this->audit->record(
                module: 'Repair',
                eventType: 'RepairCancelled',
                actorStaffId: new StaffId($command->cancelledByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'repair_job',
                subjectId: $jobId->value,
                beforeState: ['status' => $previous],
                afterState: ['status' => 'cancelled', 'reason' => $command->reason],
            );
        });
    }
}
