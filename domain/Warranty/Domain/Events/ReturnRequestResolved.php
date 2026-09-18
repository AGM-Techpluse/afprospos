<?php

declare(strict_types=1);

namespace Domain\Warranty\Domain\Events;

use Carbon\CarbonImmutable;

final readonly class ReturnRequestResolved
{
    public function __construct(
        public int $returnRequestId,
        public int $customerId,
        public CarbonImmutable $occurredAt,
    ) {}
}
