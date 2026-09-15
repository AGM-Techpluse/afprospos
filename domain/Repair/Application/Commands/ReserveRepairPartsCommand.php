<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

final readonly class ReserveRepairPartsCommand
{
    public function __construct(
        public int $repairJobId,
        public int $skuId,
        public int $quantity,
        public int $reservedByStaffId,
    ) {}
}
