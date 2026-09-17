<?php

declare(strict_types=1);

namespace Domain\Repair\Infrastructure\Laravel;

use Domain\Repair\Application\Contracts\RepairJobLookup;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;

/** The Contract's implementation — Eloquent is permitted here (Infrastructure query layer, CPNC §2.1). */
final class EloquentRepairJobLookup implements RepairJobLookup
{
    public function find(int $repairJobId): ?array
    {
        $job = RepairJobRecord::query()->find($repairJobId);

        if ($job === null) {
            return null;
        }

        return [
            'id' => $job->id,
            'shop_id' => $job->shop_id,
            'customer_id' => $job->customer_id,
            'device_make' => $job->device_make,
            'device_model' => $job->device_model,
            'repair_status' => $job->repair_status,
            'created_at' => $job->created_at->toIso8601String(),
        ];
    }
}
