<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Commands;

/** Several specific serialized units, moved shop to shop in one submission — see InitiateBulkSerializedTransferHandler. */
final readonly class InitiateBulkSerializedTransferCommand
{
    /** @param  array<int, array{sku_id: int, inventory_item_id: int}>  $units */
    public function __construct(
        public array $units,
        public int $fromShopId,
        public int $toShopId,
        public int $initiatedByStaffId,
    ) {}
}
