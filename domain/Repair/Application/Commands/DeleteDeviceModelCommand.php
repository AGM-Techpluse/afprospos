<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

final readonly class DeleteDeviceModelCommand
{
    public function __construct(
        public int $deviceModelId,
        public int $deletedByStaffId,
    ) {}
}
