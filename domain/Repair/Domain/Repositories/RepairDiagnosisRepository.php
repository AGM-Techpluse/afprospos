<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Repositories;

use Domain\Repair\Domain\Entities\RepairDiagnosis;
use Domain\Repair\Domain\ValueObjects\RepairJobId;

interface RepairDiagnosisRepository
{
    /** @return RepairDiagnosis[] */
    public function findByRepairJob(RepairJobId $repairJobId): array;

    /** Append-only — a finalized diagnosis observation is never updated. */
    public function save(RepairDiagnosis $diagnosis): void;
}
