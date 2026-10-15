<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

final readonly class TransferRepairJobCommand
{
    public function __construct(
        public int $repairJobId,
        public int $targetShopId,
        public int $transferredByStaffId,
        public bool $releaseReservedParts,
        public ?string $reason = null,
    ) {}
}
