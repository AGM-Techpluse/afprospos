<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Repositories;

use Domain\Repair\Domain\Entities\DiagnosisConclusion;
use Domain\Repair\Domain\ValueObjects\RepairJobId;

interface DiagnosisConclusionRepository
{
    /** @return DiagnosisConclusion[] */
    public function findByRepairJob(RepairJobId $repairJobId): array;

    /** Append-only — a recorded conclusion is never updated. */
    public function save(DiagnosisConclusion $conclusion): void;
}
