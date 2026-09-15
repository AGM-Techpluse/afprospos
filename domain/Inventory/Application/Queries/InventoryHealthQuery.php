<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Queries;

use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryStockLevelRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;

/** Backs the Admin Dashboard's inventory-health summary — a rollup on top of the same Available (on_hand - reserved) rule LowStockQuery already uses (DBDD §27). */
final class InventoryHealthQuery
{
    public function __construct(private readonly LowStockQuery $lowStock) {}

    /** @return array{total_skus: int, low_stock_count: int, out_of_stock_count: int} */
    public function forShop(?int $shopId): array
    {
        $levelsQuery = InventoryStockLevelRecord::query();

        if ($shopId !== null) {
            $levelsQuery->where('shop_id', $shopId);
        }

        $outOfStockCount = (clone $levelsQuery)
            ->whereColumn('on_hand', '<=', 'reserved')
            ->count();

        return [
            'total_skus' => SkuRecord::query()->count(),
            'low_stock_count' => count($this->lowStock->forShop($shopId)),
            'out_of_stock_count' => $outOfStockCount,
        ];
    }
}
