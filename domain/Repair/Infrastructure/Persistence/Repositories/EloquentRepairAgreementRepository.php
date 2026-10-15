<?php

declare(strict_types=1);

namespace Domain\Repair\Infrastructure\Persistence\Repositories;

use Domain\Repair\Domain\Repositories\RepairAgreementRepository;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairAgreementRecord;

final class EloquentRepairAgreementRepository implements RepairAgreementRepository
{
    public function record(int $repairJobId, string $note, int $recordedByStaffId): void
    {
        RepairAgreementRecord::query()->create([
            'repair_job_id' => $repairJobId,
            'note' => $note,
            'recorded_by_staff_id' => $recordedByStaffId,
        ]);
    }

    public function latestNote(int $repairJobId): ?string
    {
        return RepairAgreementRecord::query()->where('repair_job_id', $repairJobId)->latest('id')->value('note');
    }
}
