<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Commands;

final readonly class ReceiveInventoryTransferCommand
{
    public function __construct(
        public int $transferId,
        public int $receivedByStaffId,
    ) {}
}
