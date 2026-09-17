<?php

declare(strict_types=1);

namespace Domain\Warranty\Domain\Events;

use Carbon\CarbonImmutable;

final readonly class WarrantyClaimResolved
{
    public function __construct(
        public int $warrantyClaimId,
        public string $selectedRemedy,
        public CarbonImmutable $occurredAt,
    ) {}
}
