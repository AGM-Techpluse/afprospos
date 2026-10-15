<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

/** Sets or revises the customer-agreed repair price, recorded after a `repairable` diagnosis conclusion — see `RepairJob::setRepairCost()` for the editable-state guard. */
final readonly class SetRepairCostCommand
{
    public function __construct(
        public int $repairJobId,
        public int $repairCostMinor,
        public int $setByStaffId,
    ) {}
}
