<?php

declare(strict_types=1);

namespace Domain\Inventory\Domain\Repositories;

use Domain\Inventory\Domain\Entities\InventoryTransfer;
use Domain\Inventory\Domain\ValueObjects\InventoryTransferId;
use Domain\Inventory\Domain\ValueObjects\SkuId;
use Domain\Shared\Domain\ValueObjects\ShopId;
use Domain\Shared\Domain\ValueObjects\StaffId;

interface InventoryTransferRepository
{
    public function get(InventoryTransferId $id): InventoryTransfer;

    public function create(
        SkuId $skuId,
        ?int $inventoryItemId,
        ?int $quantity,
        ShopId $fromShopId,
        ShopId $toShopId,
        StaffId $initiatedByStaffId,
    ): InventoryTransferId;

    public function save(InventoryTransfer $transfer): void;
}
