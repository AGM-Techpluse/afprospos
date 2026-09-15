<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Events;

use Carbon\CarbonImmutable;

final readonly class RepairDiagnosed
{
    public function __construct(
        public int $repairJobId,
        public string $outcome,
        public CarbonImmutable $occurredAt,
    ) {}
}
