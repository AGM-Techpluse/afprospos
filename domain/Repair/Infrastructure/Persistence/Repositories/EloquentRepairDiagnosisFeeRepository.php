<?php

declare(strict_types=1);

namespace Domain\Repair\Infrastructure\Persistence\Repositories;

use Domain\Repair\Domain\Entities\RepairDiagnosisFee;
use Domain\Repair\Domain\Repositories\RepairDiagnosisFeeRepository;
use Domain\Repair\Domain\ValueObjects\RepairJobId;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairDiagnosisFeeRecord;

final class EloquentRepairDiagnosisFeeRepository implements RepairDiagnosisFeeRepository
{
    public function find(RepairJobId $repairJobId): ?RepairDiagnosisFee
    {
        $record = RepairDiagnosisFeeRecord::query()->where('repair_job_id', $repairJobId->value)->first();

        return $record === null ? null : RepairDiagnosisFee::reconstitute(
            $repairJobId,
            (int) $record->amount_minor,
            $record->decision,
            (int) $record->set_by_staff_id,
            $record->decided_by_staff_id !== null ? (int) $record->decided_by_staff_id : null,
        );
    }

    public function save(RepairDiagnosisFee $fee): void
    {
        RepairDiagnosisFeeRecord::query()->updateOrCreate(
            ['repair_job_id' => $fee->repairJobId()->value],
            [
                'amount_minor' => $fee->amountMinor(),
                'decision' => $fee->decision(),
                'set_by_staff_id' => $fee->setByStaffId(),
                'decided_by_staff_id' => $fee->decidedByStaffId(),
                'decided_at' => $fee->decidedByStaffId() !== null ? now() : null,
            ],
        );
    }
}
