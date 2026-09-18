<?php

declare(strict_types=1);

namespace Domain\Warranty\Application\Commands;

final readonly class SubmitTradeInAssessmentCommand
{
    public function __construct(
        public int $customerId,
        public ?int $relatedCheckoutId,
        public array $deviceDescription,
        public int $submittedByStaffId,
    ) {}
}
