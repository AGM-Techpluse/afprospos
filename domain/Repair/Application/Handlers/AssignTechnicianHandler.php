<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Repair\Application\Commands\AssignTechnicianCommand;
use Domain\Repair\Domain\Repositories\RepairJobRepository;
use Domain\Repair\Domain\ValueObjects\RepairJobId;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class AssignTechnicianHandler
{
    public function __construct(
        private readonly RepairJobRepository $repairJobs,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(AssignTechnicianCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $jobId = new RepairJobId($command->repairJobId);
            $job = $this->repairJobs->lockForUpdate($jobId);
            $before = $job->technicianStaffId()?->value;

            $job->assignTechnician(new StaffId($command->technicianStaffId));
            $this->repairJobs->save($job);

            $this->audit->record(
                module: 'Repair',
                eventType: 'TechnicianAssigned',
                actorStaffId: new StaffId($command->assignedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'repair_job',
                subjectId: $jobId->value,
                beforeState: ['technician_staff_id' => $before],
                afterState: ['technician_staff_id' => $command->technicianStaffId],
            );
        });
    }
}
