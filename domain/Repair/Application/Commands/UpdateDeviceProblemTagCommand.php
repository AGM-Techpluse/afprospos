<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

final readonly class UpdateDeviceProblemTagCommand
{
    public function __construct(
        public int $deviceProblemTagId,
        public string $label,
        public int $sortOrder,
        public int $updatedByStaffId,
    ) {}
}
