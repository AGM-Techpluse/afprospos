<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Events;

use Carbon\CarbonImmutable;

final readonly class RepairCompleted
{
    public function __construct(
        public int $repairJobId,
        public string $financialStatus,
        public CarbonImmutable $occurredAt,
    ) {}
}
