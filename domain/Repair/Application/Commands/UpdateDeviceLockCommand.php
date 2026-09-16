<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

final readonly class UpdateDeviceLockCommand
{
    /** @param  'none'|'code'|'pattern'  $deviceLockType */
    public function __construct(
        public int $repairJobId,
        public string $deviceLockType,
        public ?string $deviceLockValue,
        public int $updatedByStaffId,
    ) {}
}
