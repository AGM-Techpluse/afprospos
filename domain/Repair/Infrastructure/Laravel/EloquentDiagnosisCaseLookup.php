<?php

declare(strict_types=1);

namespace Domain\Repair\Infrastructure\Laravel;

use Domain\Repair\Application\Contracts\DiagnosisCaseLookup;
use Domain\Repair\Infrastructure\Persistence\Eloquent\DiagnosisCaseRecord;

/** The Contract's implementation — Eloquent is permitted here (Infrastructure query layer, CPNC §2.1). */
final class EloquentDiagnosisCaseLookup implements DiagnosisCaseLookup
{
    public function find(int $diagnosisCaseId): ?array
    {
        $case = DiagnosisCaseRecord::query()->find($diagnosisCaseId);

        if ($case === null) {
            return null;
        }

        return [
            'id' => $case->id,
            'shop_id' => $case->shop_id,
            'customer_id' => $case->customer_id,
            'walk_in_name' => $case->walk_in_name,
            'device_make' => $case->device_make,
            'device_model' => $case->device_model,
            'status' => $case->status,
            'created_at' => $case->created_at->toIso8601String(),
        ];
    }
}
