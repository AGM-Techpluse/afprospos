<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

/** The decision on a requested supplemental quote — approving makes it count toward the outstanding balance; rejecting leaves the quote total untouched. */
final readonly class DecideSupplementalQuoteCommand
{
    public function __construct(
        public int $quoteId,
        public bool $approved,
        public int $decidedByStaffId,
    ) {}
}
