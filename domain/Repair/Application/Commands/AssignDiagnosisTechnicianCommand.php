<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

final readonly class AssignDiagnosisTechnicianCommand
{
    public function __construct(
        public int $diagnosisCaseId,
        public int $technicianStaffId,
        public int $assignedByStaffId,
    ) {}
}
