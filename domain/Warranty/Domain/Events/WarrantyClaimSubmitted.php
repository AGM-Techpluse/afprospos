<?php

declare(strict_types=1);

namespace Domain\Warranty\Domain\Events;

use Carbon\CarbonImmutable;

final readonly class WarrantyClaimSubmitted
{
    public function __construct(
        public int $warrantyClaimId,
        public int $warrantyPolicyId,
        public int $customerId,
        public CarbonImmutable $occurredAt,
    ) {}
}
