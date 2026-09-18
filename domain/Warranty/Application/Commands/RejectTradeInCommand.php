<?php

declare(strict_types=1);

namespace Domain\Warranty\Application\Commands;

final readonly class RejectTradeInCommand
{
    public function __construct(
        public int $tradeInAssessmentId,
        public int $rejectedByStaffId,
    ) {}
}
