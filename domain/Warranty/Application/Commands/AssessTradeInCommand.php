<?php

declare(strict_types=1);

namespace Domain\Warranty\Application\Commands;

final readonly class AssessTradeInCommand
{
    public function __construct(
        public int $tradeInAssessmentId,
        public int $assessedValueMinor,
        public int $assessedByStaffId,
    ) {}
}
