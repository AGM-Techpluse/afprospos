<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

/** A technician-typed "newly discovered fixed fault" not yet in the catalog — see AddFixedFaultToCatalogHandler. */
final readonly class AddFixedFaultToCatalogCommand
{
    public function __construct(
        public int $repairJobId,
        public string $label,
        public ?int $commissionAmountMinor,
        public int $addedByStaffId,
    ) {}
}
