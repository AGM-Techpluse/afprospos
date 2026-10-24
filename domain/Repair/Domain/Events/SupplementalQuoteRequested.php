<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Events;

use Carbon\CarbonImmutable;

/** Additional cost has been flagged mid-repair — needs the same trust tier that set the original quote to approve before it counts toward the outstanding balance. */
final readonly class SupplementalQuoteRequested
{
    public function __construct(
        public int $repairJobId,
        public int $shopId,
        public int $requestedByStaffId,
        public CarbonImmutable $occurredAt,
    ) {}
}
