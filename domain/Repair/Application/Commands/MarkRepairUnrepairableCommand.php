<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

final readonly class MarkRepairUnrepairableCommand
{
    /** @param  'pending_decision'|'refunded'|'retained'  $settlementState */
    public function __construct(
        public int $repairJobId,
        public string $settlementState,
        public int $markedByStaffId,
    ) {}
}
