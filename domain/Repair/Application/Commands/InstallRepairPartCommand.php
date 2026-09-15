<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

/** The real Inventory-consumption point — reservation never silently consumes stock. */
final readonly class InstallRepairPartCommand
{
    public function __construct(
        public int $repairPartReservationId,
    ) {}
}
