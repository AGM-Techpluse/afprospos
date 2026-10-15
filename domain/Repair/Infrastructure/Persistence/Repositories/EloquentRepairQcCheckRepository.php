<?php

declare(strict_types=1);

namespace Domain\Repair\Infrastructure\Persistence\Repositories;

use Domain\Repair\Domain\Repositories\RepairQcCheckRepository;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairQcCheckRecord;

final class EloquentRepairQcCheckRepository implements RepairQcCheckRepository
{
    public function record(int $repairJobId, string $outcome, ?string $notes, int $checkedByStaffId): void
    {
        RepairQcCheckRecord::query()->create([
            'repair_job_id' => $repairJobId,
            'outcome' => $outcome,
            'notes' => $notes,
            'checked_by_staff_id' => $checkedByStaffId,
        ]);
    }
}
