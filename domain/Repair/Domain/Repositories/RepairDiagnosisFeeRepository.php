<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Repositories;

use Domain\Repair\Domain\Entities\RepairDiagnosisFee;
use Domain\Repair\Domain\ValueObjects\RepairJobId;

interface RepairDiagnosisFeeRepository
{
    public function find(RepairJobId $repairJobId): ?RepairDiagnosisFee;

    public function save(RepairDiagnosisFee $fee): void;
}
