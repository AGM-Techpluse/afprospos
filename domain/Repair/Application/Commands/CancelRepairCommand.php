<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

final readonly class CancelRepairCommand
{
    public function __construct(
        public int $repairJobId,
        public string $reason,
        public int $cancelledByStaffId,
    ) {}
}
