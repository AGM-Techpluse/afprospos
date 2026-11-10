<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

/** Clones a batch of already-known fault labels onto one model in a single submission, instead of retyping each one via CreateDeviceProblemTagCommand. */
final readonly class BulkAddDeviceProblemTagsCommand
{
    /** @param  array<int, string>  $labels */
    public function __construct(
        public int $deviceModelId,
        public array $labels,
        public int $startingSortOrder,
        public int $createdByStaffId,
    ) {}
}
