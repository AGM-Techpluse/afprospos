<?php

declare(strict_types=1);

namespace Domain\Repair\Infrastructure\Persistence\Repositories;

use Domain\Repair\Domain\Entities\DiagnosisConclusion;
use Domain\Repair\Domain\Repositories\DiagnosisConclusionRepository;
use Domain\Repair\Domain\ValueObjects\DiagnosisConclusionId;
use Domain\Repair\Domain\ValueObjects\RepairJobId;
use Domain\Repair\Infrastructure\Persistence\Eloquent\DiagnosisConclusionRecord;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class EloquentDiagnosisConclusionRepository implements DiagnosisConclusionRepository
{
    public function findByRepairJob(RepairJobId $repairJobId): array
    {
        return DiagnosisConclusionRecord::query()
            ->where('repair_job_id', $repairJobId->value)
            ->orderBy('created_at')
            ->get()
            ->map(fn (DiagnosisConclusionRecord $record): DiagnosisConclusion => $this->toDomain($record))
            ->all();
    }

    public function save(DiagnosisConclusion $conclusion): void
    {
        DiagnosisConclusionRecord::query()->create([
            'repair_job_id' => $conclusion->repairJobId()->value,
            'root_cause' => $conclusion->rootCause(),
            'outcome' => $conclusion->outcome(),
            'diagnosed_by_staff_id' => $conclusion->diagnosedByStaffId()->value,
        ]);
    }

    private function toDomain(DiagnosisConclusionRecord $record): DiagnosisConclusion
    {
        return DiagnosisConclusion::reconstitute(
            new DiagnosisConclusionId($record->id),
            new RepairJobId($record->repair_job_id),
            $record->root_cause,
            $record->outcome,
            new StaffId($record->diagnosed_by_staff_id),
        );
    }
}
