<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Events;

use Carbon\CarbonImmutable;

final readonly class RepairReadyForCollection
{
    public function __construct(
        public int $repairJobId,
        public int $collectionCaseId,
        public CarbonImmutable $occurredAt,
    ) {}
}
