<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Commands;

final readonly class CancelInventoryTransferCommand
{
    public function __construct(
        public int $transferId,
        public int $cancelledByStaffId,
    ) {}
}
