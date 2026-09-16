<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

final readonly class DeleteDeviceBrandCommand
{
    public function __construct(
        public int $deviceBrandId,
        public int $deletedByStaffId,
    ) {}
}
