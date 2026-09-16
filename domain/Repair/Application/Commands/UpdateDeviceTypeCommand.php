<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

final readonly class UpdateDeviceTypeCommand
{
    public function __construct(
        public int $deviceTypeId,
        public string $label,
        public string $icon,
        public int $sortOrder,
        public int $updatedByStaffId,
    ) {}
}
