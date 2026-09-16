<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

final readonly class CreateDeviceTypeCommand
{
    public function __construct(
        public string $label,
        public string $icon,
        public int $sortOrder,
        public int $createdByStaffId,
    ) {}
}
