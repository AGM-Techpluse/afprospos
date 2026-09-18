<?php

declare(strict_types=1);

namespace Domain\Warranty\Domain\Events;

use Carbon\CarbonImmutable;

final readonly class TradeInCreditApplied
{
    public function __construct(
        public int $tradeInAssessmentId,
        public int $customerId,
        public CarbonImmutable $occurredAt,
    ) {}
}
