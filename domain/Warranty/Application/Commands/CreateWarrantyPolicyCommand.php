<?php

declare(strict_types=1);

namespace Domain\Warranty\Application\Commands;

final readonly class CreateWarrantyPolicyCommand
{
    /**
     * @param  array<int, string>  $coveredScope
     * @param  array<int, string>  $exclusions
     * @param  array<int, string>  $availableRemedies
     */
    public function __construct(
        public string $name,
        public int $coverageDurationDays,
        public string $coverageStartPoint,
        public array $coveredScope,
        public array $exclusions,
        public array $availableRemedies,
        public string $coverageExtent,
        public int $createdByStaffId,
    ) {}
}
