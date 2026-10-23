<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Commission\Application\Commands\RecordCommissionEarnedCommand;
use Domain\Commission\Application\Contracts\CommissionRecordingService;
use Domain\Repair\Application\Commands\CompleteRepairCommand;
use Domain\Repair\Application\Services\ReleaseRepairPartsService;
use Domain\Repair\Domain\Entities\RepairJob;
use Domain\Repair\Domain\Repositories\RepairJobRepository;
use Domain\Repair\Domain\ValueObjects\RepairJobId;
use Domain\Repair\Infrastructure\Persistence\Eloquent\CommonComponentRecord;
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
        private readonly ReleaseRepairPartsService $releaseParts,
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

            // A part reserved but never marked installed was never actually used — release it back to Inventory now rather than leaving that stock silently locked forever (same policy TransferRepairJobHandler already applies on transfer/cancel).
            $this->releaseParts->releaseOutstanding($jobId, $job->shopId()->value);

            RepairFixedFaultRecord::query()->where('repair_job_id', $jobId->value)->delete();
            foreach ($command->fixedFaults as $fault) {
                $label = trim((string) ($fault['label'] ?? ''));
                if ($label === '') {
                    continue;
                }

                $deviceProblemTagId = isset($fault['device_problem_tag_id']) ? (int) $fault['device_problem_tag_id'] : null;
                $commonComponentId = isset($fault['common_component_id']) ? (int) $fault['common_component_id'] : null;

                // The picker always sends the amount it actually credited (even 0 for a non-commissionable fault) — a fault submitted without the key at all (e.g. a quick-check-sourced one the picker never touches) falls back to a fresh catalog lookup, same as before this change.
                $commissionAmountMinor = array_key_exists('commission_amount_minor', $fault) && $fault['commission_amount_minor'] !== null
                    ? (int) $fault['commission_amount_minor']
                    : $this->catalogCommissionAmount($deviceProblemTagId, $commonComponentId);

                RepairFixedFaultRecord::query()->create([
                    'repair_job_id' => $jobId->value,
                    'diagnosis_case_fault_id' => isset($fault['diagnosis_case_fault_id']) ? (int) $fault['diagnosis_case_fault_id'] : null,
                    'device_problem_tag_id' => $deviceProblemTagId,
                    'common_component_id' => $commonComponentId,
                    'label_snapshot' => $label,
                    'notes' => isset($fault['notes']) && trim((string) $fault['notes']) !== '' ? trim((string) $fault['notes']) : null,
                    'commission_amount_minor' => $commissionAmountMinor,
                    'recorded_by_staff_id' => $command->completedByStaffId,
                ]);

                if (($deviceProblemTagId !== null || $commonComponentId !== null) && $commissionAmountMinor !== null) {
                    $this->creditFaultFixCommission($job, $jobId, $commissionAmountMinor);
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

    /** No technician assigned, or the amount is non-positive, simply earns nothing — this is a silent skip, not an error. */
    private function creditFaultFixCommission(RepairJob $job, RepairJobId $jobId, int $amountMinor): void
    {
        $technicianId = $job->technicianStaffId();

        if ($technicianId === null || $amountMinor <= 0) {
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

    /** Fallback only — a fault submitted without its own commission_amount_minor (e.g. a quick-check-sourced one the new device-specific picker never touches). A catalog-diagnosed tag takes priority if a fault somehow carried both ids. */
    private function catalogCommissionAmount(?int $deviceProblemTagId, ?int $commonComponentId): ?int
    {
        if ($deviceProblemTagId !== null) {
            return DeviceProblemTagRecord::query()->find($deviceProblemTagId)?->commission_amount_minor;
        }

        if ($commonComponentId !== null) {
            return CommonComponentRecord::query()->find($commonComponentId)?->commission_amount_minor;
        }

        return null;
    }
}
