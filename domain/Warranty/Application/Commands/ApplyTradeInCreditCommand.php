<?php

declare(strict_types=1);

namespace Domain\Warranty\Application\Commands;

/** The manual fallback when an approved trade-in had no checkout linked yet (or its original checkout closed before the credit could auto-apply). */
final readonly class ApplyTradeInCreditCommand
{
    public function __construct(
        public int $tradeInAssessmentId,
        public int $checkoutId,
        public int $appliedByStaffId,
    ) {}
}
