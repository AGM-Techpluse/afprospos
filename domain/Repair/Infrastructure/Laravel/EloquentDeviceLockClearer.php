<?php

declare(strict_types=1);

namespace Domain\Repair\Infrastructure\Laravel;

use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Repair\Application\Contracts\DeviceLockClearer;
use Domain\Repair\Domain\Repositories\RepairJobRepository;
use Domain\Repair\Domain\ValueObjects\RepairJobId;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class EloquentDeviceLockClearer implements DeviceLockClearer
{
    public function __construct(
        private readonly RepairJobRepository $repairJobs,
        private readonly AuditWriter $audit,
    ) {}

    /** No-op (and no audit row) when there was nothing to clear — the caller (ReleaseRepairDeviceHandler) already runs inside its own transaction, so this participates in that same one rather than opening a second. */
    public function clear(int $repairJobId, int $clearedByStaffId): void
    {
        $id = new RepairJobId($repairJobId);
        $job = $this->repairJobs->lockForUpdate($id);

        if ($job->deviceLockType() === 'none') {
            return;
        }

        $job->clearDeviceLock();
        $this->repairJobs->save($job);

        $this->audit->record(
            module: 'Repair',
            eventType: 'DeviceLockCleared',
            actorStaffId: new StaffId($clearedByStaffId),
            actorRoleSnapshot: null,
            subjectType: 'repair_job',
            subjectId: $repairJobId,
            beforeState: null,
            afterState: null,
        );
    }
}
