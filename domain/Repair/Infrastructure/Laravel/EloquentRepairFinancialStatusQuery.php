<?php

declare(strict_types=1);

namespace Domain\Repair\Infrastructure\Laravel;

use Domain\Repair\Application\Contracts\RepairFinancialStatusQuery;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;

/** The Contract's implementation — Eloquent is permitted here (Infrastructure query layer, CPNC §2.1). */
final class EloquentRepairFinancialStatusQuery implements RepairFinancialStatusQuery
{
    public function financialStatusFor(int $repairJobId): string
    {
        return RepairJobRecord::query()->findOrFail($repairJobId)->financial_status;
    }
}
