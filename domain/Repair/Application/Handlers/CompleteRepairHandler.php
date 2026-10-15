<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Commission\Application\Commands\RecordCommissionEarnedCommand;
use Domain\Commission\Application\Contracts\CommissionRecordingService;
use Domain\Repair\Application\Commands\CompleteRepairCommand;
use Domain\Repair\Domain\Entities\RepairJob;
use Domain\Repair\Domain\Repositories\RepairJobRepository;
use Domain\Repair\Domain\ValueObjects\RepairJobId;
use Domain\Repair\Infrastructure\Persistence\Eloquent\DeviceProblemTagRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairFixedFaultRecord;
use Domain\Shared\Domain\ValueObjects\StaffId;

/**
 * The technician has finished the work. The job now waits for a quality check;
 * the collection case and the ready-for-collection notice are created only once
 * QC passes (RecordQualityCheckHandler).
 */
final class CompleteRepairHandler
{
    public function __construct(
        private readonly RepairJobRepository $repairJobs,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
        private readonly CommissionRecordingService $commission,
    ) {}

    public function handle(CompleteRepairCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $jobId = new RepairJobId($command->repairJobId);
            $job = $this->repairJobs->lockForUpdate($jobId);

            $job->complete($command->resolutionNotes);
            $this->repairJobs->save($job);

            RepairFixedFaultRecord::query()->where('repair_job_id', $jobId->value)->delete();
            foreach ($command->fixedFaults as $fault) {
                $label = trim((string) ($fault['label'] ?? ''));
                if ($label === '') {
                    continue;
                }

                RepairFixedFaultRecord::query()->create([
                    'repair_job_id' => $jobId->value,
                    'diagnosis_case_fault_id' => isset($fault['diagnosis_case_fault_id']) ? (int) $fault['diagnosis_case_fault_id'] : null,
                    'device_problem_tag_id' => isset($fault['device_problem_tag_id']) ? (int) $fault['device_problem_tag_id'] : null,
                    'label_snapshot' => $label,
                    'notes' => isset($fault['notes']) && trim((string) $fault['notes']) !== '' ? trim((string) $fault['notes']) : null,
                    'recorded_by_staff_id' => $command->completedByStaffId,
                ]);

                if ($fault['device_problem_tag_id'] ?? null) {
                    $this->creditFaultFixCommission($job, $jobId, (int) $fault['device_problem_tag_id']);
                }
            }

            $this->audit->record(
                module: 'Repair',
                eventType: 'RepairWorkCompleted',
                actorStaffId: new StaffId($command->completedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'repair_job',
                subjectId: $jobId->value,
                beforeState: null,
                afterState: ['resolution_notes' => $command->resolutionNotes, 'fixed_faults' => $command->fixedFaults],
            );
        });
    }

    /** No technician assigned, or the tag carries no commission amount, simply earns nothing — this is a silent skip, not an error. */
    private function creditFaultFixCommission(RepairJob $job, RepairJobId $jobId, int $deviceProblemTagId): void
    {
        $technicianId = $job->technicianStaffId();
        $amountMinor = DeviceProblemTagRecord::query()->find($deviceProblemTagId)?->commission_amount_minor;

        if ($technicianId === null || $amountMinor === null || $amountMinor <= 0) {
            return;
        }

        $this->commission->recordEarned(new RecordCommissionEarnedCommand(
            staffId: $technicianId->value,
            sourceType: 'repair',
            sourceId: $jobId->value,
            amountMinor: $amountMinor,
            shopId: $job->shopId()->value,
        ));
    }
}
