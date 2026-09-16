<?php

declare(strict_types=1);

namespace Domain\Repair\Infrastructure\Persistence\Repositories;

use Domain\Repair\Domain\Repositories\RepairJobProblemTagRepository;
use Domain\Repair\Domain\ValueObjects\RepairJobId;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobProblemTagRecord;

final class EloquentRepairJobProblemTagRepository implements RepairJobProblemTagRepository
{
    public function attach(RepairJobId $repairJobId, ?int $deviceProblemTagId, string $labelSnapshot): void
    {
        RepairJobProblemTagRecord::query()->create([
            'repair_job_id' => $repairJobId->value,
            'device_problem_tag_id' => $deviceProblemTagId,
            'label_snapshot' => $labelSnapshot,
        ]);
    }

    public function forJob(RepairJobId $repairJobId): array
    {
        return RepairJobProblemTagRecord::query()
            ->where('repair_job_id', $repairJobId->value)
            ->get()
            ->map(static fn (RepairJobProblemTagRecord $record): array => [
                'id' => $record->id,
                'device_problem_tag_id' => $record->device_problem_tag_id,
                'label_snapshot' => $record->label_snapshot,
            ])->all();
    }
}
