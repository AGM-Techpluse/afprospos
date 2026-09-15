<?php

declare(strict_types=1);

namespace Domain\Collection\Application\Commands;

/** Manual only — never dispatched automatically (Phase 6 scope decision: notifications stay Phase 8's job). */
final readonly class RecordCollectionNotifiedCommand
{
    public function __construct(
        public int $collectionCaseId,
        public int $notifiedByStaffId,
        public ?string $note,
    ) {}
}
