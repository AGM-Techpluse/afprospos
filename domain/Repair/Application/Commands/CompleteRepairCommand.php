<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

final readonly class CompleteRepairCommand
{
    /** @param  'unpaid'|'partially_paid'|'fully_paid'  $financialStatus */
    public function __construct(
        public int $repairJobId,
        public string $financialStatus,
        public int $completedByStaffId,
    ) {}
}
