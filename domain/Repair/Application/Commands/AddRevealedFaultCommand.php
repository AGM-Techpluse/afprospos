<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

/** A technician-typed "unique revealed fault" label, not yet in the catalog — see AddRevealedFaultHandler. */
final readonly class AddRevealedFaultCommand
{
    public function __construct(
        public int $diagnosisCaseId,
        public string $label,
        public int $addedByStaffId,
    ) {}
}
