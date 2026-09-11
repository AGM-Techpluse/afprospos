<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Commands;

/**
 * BRD INV-10 / INV-BR-11. Either inventoryItemId (serialized) or
 * quantity (bulk) is set, never both — mirrors DBDD §14.5's
 * inventory_transfers columns exactly.
 */
final readonly class InitiateInventoryTransferCommand
{
    public function __construct(
        public int $skuId,
        public ?int $inventoryItemId,
        public ?int $quantity,
        public int $fromShopId,
        public int $toShopId,
        public int $initiatedByStaffId,
    ) {}
}
