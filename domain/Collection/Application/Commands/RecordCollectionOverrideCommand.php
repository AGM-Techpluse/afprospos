<?php

declare(strict_types=1);

namespace Domain\Collection\Application\Commands;

/** Must be recorded before a ReleaseRepairDeviceCommand can succeed with an outstanding balance — the audit event always exists before the state transition. */
final readonly class RecordCollectionOverrideCommand
{
    public function __construct(
        public int $collectionCaseId,
        public int $overriddenByStaffId,
        public string $reason,
    ) {}
}
