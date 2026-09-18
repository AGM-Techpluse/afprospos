<?php

declare(strict_types=1);

namespace Domain\Sales\Domain\Events;

use Carbon\CarbonImmutable;

/** `customerId` is null for a walk-in sale — no registered customer to notify. */
final readonly class SaleCompleted
{
    public function __construct(
        public int $saleId,
        public ?int $customerId,
        public CarbonImmutable $occurredAt,
    ) {}
}
