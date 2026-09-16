<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

final readonly class DeleteDeviceProblemTagCommand
{
    public function __construct(
        public int $deviceProblemTagId,
        public int $deletedByStaffId,
    ) {}
}
