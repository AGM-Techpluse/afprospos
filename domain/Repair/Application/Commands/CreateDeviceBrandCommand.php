<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

final readonly class CreateDeviceBrandCommand
{
    public function __construct(
        public int $deviceTypeId,
        public string $name,
        public int $sortOrder,
        public int $createdByStaffId,
    ) {}
}
