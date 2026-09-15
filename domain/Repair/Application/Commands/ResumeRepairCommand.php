<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

final readonly class ResumeRepairCommand
{
    public function __construct(
        public int $repairJobId,
        public int $resumedByStaffId,
    ) {}
}
