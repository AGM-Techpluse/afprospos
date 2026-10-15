<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

/**
 * `outcome`: passed (works as repaired), failed (does not work), or not_testable
 * (the device could not be fully tested, e.g. missing SIM/accessories). Notes are
 * required for failed and not_testable; the request enforces that.
 */
final readonly class RecordQualityCheckCommand
{
    public function __construct(
        public int $repairJobId,
        public string $outcome,
        public ?string $notes,
        public int $checkedByStaffId,
    ) {}
}
