<?php

declare(strict_types=1);

namespace Domain\Inventory\Infrastructure\Laravel;

use Domain\Inventory\Application\Contracts\ShopStockReader;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryItemRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryStockLevelRecord;

final class EloquentShopStockReader implements ShopStockReader
{
    public function stockUnits(int $shopId): int
    {
        $nonSerialized = (int) InventoryStockLevelRecord::query()->where('shop_id', $shopId)->sum('on_hand');

        $serialized = InventoryItemRecord::query()
            ->where('current_shop_id', $shopId)
            ->whereIn('status', ['available', 'reserved'])
            ->count();

        return $nonSerialized + $serialized;
    }
}
