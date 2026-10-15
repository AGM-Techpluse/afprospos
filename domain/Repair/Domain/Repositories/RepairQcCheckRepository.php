<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Repositories;

/** Append-only history of quality checks. A job can be checked more than once (a failed check can loop back to diagnosis). */
interface RepairQcCheckRepository
{
    public function record(int $repairJobId, string $outcome, ?string $notes, int $checkedByStaffId): void;
}
