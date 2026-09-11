<?php

declare(strict_types=1);

namespace Domain\Inventory\Domain\Repositories;

use Domain\Inventory\Domain\Entities\InventoryStockLevel;
use Domain\Inventory\Domain\ValueObjects\SkuId;
use Domain\Shared\Domain\ValueObjects\ShopId;

interface InventoryStockLevelRepository
{
    public function find(SkuId $skuId, ShopId $shopId): ?InventoryStockLevel;

    /**
     * Locks the (sku_id, shop_id) row with SELECT ... FOR UPDATE
     * (DBDD §25.1) — the serialization point for competing
     * reservations. Creates the row (on_hand=0, reserved=0) first if
     * it doesn't exist yet, so every SKU/shop pair can be locked from
     * its very first stock movement.
     */
    public function lockForUpdate(SkuId $skuId, ShopId $shopId): InventoryStockLevel;

    public function save(InventoryStockLevel $level): void;
}
