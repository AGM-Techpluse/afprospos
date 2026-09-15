<?php

declare(strict_types=1);

namespace Domain\Collection\Application\Commands;

final readonly class ReleaseRepairDeviceCommand
{
    public function __construct(
        public int $collectionCaseId,
        public int $releasedByStaffId,
    ) {}
}
