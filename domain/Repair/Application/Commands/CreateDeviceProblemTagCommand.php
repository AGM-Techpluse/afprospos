<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

final readonly class CreateDeviceProblemTagCommand
{
    public function __construct(
        public int $deviceTypeId,
        public string $label,
        public int $sortOrder,
        public int $createdByStaffId,
    ) {}
}
