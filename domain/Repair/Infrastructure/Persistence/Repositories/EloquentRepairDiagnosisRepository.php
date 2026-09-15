<?php

declare(strict_types=1);

namespace Domain\Repair\Infrastructure\Persistence\Repositories;

use Domain\Repair\Domain\Entities\RepairDiagnosis;
use Domain\Repair\Domain\Repositories\RepairDiagnosisRepository;
use Domain\Repair\Domain\ValueObjects\RepairDiagnosisId;
use Domain\Repair\Domain\ValueObjects\RepairJobId;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairDiagnosisRecord;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class EloquentRepairDiagnosisRepository implements RepairDiagnosisRepository
{
    public function findByRepairJob(RepairJobId $repairJobId): array
    {
        return RepairDiagnosisRecord::query()
            ->where('repair_job_id', $repairJobId->value)
            ->orderBy('created_at')
            ->get()
            ->map(fn (RepairDiagnosisRecord $record): RepairDiagnosis => $this->toDomain($record))
            ->all();
    }

    public function save(RepairDiagnosis $diagnosis): void
    {
        RepairDiagnosisRecord::query()->create([
            'repair_job_id' => $diagnosis->repairJobId()->value,
            'component' => $diagnosis->component(),
            'condition' => $diagnosis->condition(),
            'notes' => $diagnosis->notes(),
            'outcome' => $diagnosis->outcome(),
            'diagnosed_by_staff_id' => $diagnosis->diagnosedByStaffId()->value,
        ]);
    }

    private function toDomain(RepairDiagnosisRecord $record): RepairDiagnosis
    {
        return RepairDiagnosis::reconstitute(
            new RepairDiagnosisId($record->id),
            new RepairJobId($record->repair_job_id),
            $record->component,
            $record->condition,
            $record->notes,
            $record->outcome,
            new StaffId($record->diagnosed_by_staff_id),
        );
    }
}
