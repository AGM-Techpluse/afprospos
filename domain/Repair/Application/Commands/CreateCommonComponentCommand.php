<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

final readonly class CreateCommonComponentCommand
{
    public function __construct(
        public string $label,
        public int $sortOrder,
        public int $createdByStaffId,
        public ?int $commissionAmountMinor = null,
    ) {}
}
