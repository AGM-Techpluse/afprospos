<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

final readonly class UpdateCommonComponentCommand
{
    public function __construct(
        public int $commonComponentId,
        public string $label,
        public int $sortOrder,
        public int $updatedByStaffId,
        public ?int $commissionAmountMinor = null,
    ) {}
}
