<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

final readonly class AssignTechnicianCommand
{
    public function __construct(
        public int $repairJobId,
        public int $technicianStaffId,
        public int $assignedByStaffId,
    ) {}
}
