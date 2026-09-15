<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Events;

use Carbon\CarbonImmutable;

final readonly class RepairCreated
{
    public function __construct(
        public int $repairJobId,
        public int $shopId,
        public int $customerId,
        public CarbonImmutable $occurredAt,
    ) {}
}
