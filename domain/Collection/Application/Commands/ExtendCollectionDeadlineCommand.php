<?php

declare(strict_types=1);

namespace Domain\Collection\Application\Commands;

use Carbon\CarbonImmutable;

final readonly class ExtendCollectionDeadlineCommand
{
    public function __construct(
        public int $collectionCaseId,
        public CarbonImmutable $newDeadlineAt,
        public ?CarbonImmutable $newAbandonmentThresholdAt,
        public int $extendedByStaffId,
        public string $reason,
    ) {}
}
