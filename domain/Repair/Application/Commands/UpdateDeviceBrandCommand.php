<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

final readonly class UpdateDeviceBrandCommand
{
    public function __construct(
        public int $deviceBrandId,
        public string $name,
        public int $sortOrder,
        public int $updatedByStaffId,
    ) {}
}
