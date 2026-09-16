<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Repair\Application\Commands\UpdateDeviceLockCommand;
use Domain\Repair\Domain\Repositories\RepairJobRepository;
use Domain\Repair\Domain\ValueObjects\RepairJobId;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class UpdateDeviceLockHandler
{
    public function __construct(
        private readonly RepairJobRepository $repairJobs,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(UpdateDeviceLockCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $id = new RepairJobId($command->repairJobId);
            $job = $this->repairJobs->lockForUpdate($id);

            $job->setDeviceLock($command->deviceLockType, $command->deviceLockValue);
            $this->repairJobs->save($job);

            $this->audit->record(
                module: 'Repair',
                eventType: 'DeviceLockUpdated',
                actorStaffId: new StaffId($command->updatedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'repair_job',
                subjectId: $id->value,
                beforeState: null,
                // device_lock_value deliberately excluded — see CreateRepairJobHandler's same note.
                afterState: ['device_lock_type' => $command->deviceLockType],
            );
        });
    }
}
