<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

/** A technician/frontdesk flags additional cost discovered mid-repair — requires the same trust tier that set the original quote to approve before it's reflected in the outstanding balance. */
final readonly class RequestSupplementalQuoteCommand
{
    public function __construct(
        public int $repairJobId,
        public int $amountMinor,
        public string $reason,
        public int $requestedByStaffId,
    ) {}
}
