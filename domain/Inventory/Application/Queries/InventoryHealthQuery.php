<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Queries;

use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;

/** Backs the Admin Dashboard's inventory-health summary — a rollup on top of the same Available (on_hand - reserved) rule LowStockQuery already uses (DBDD §27), covering serialized (IMEI-counted) and non-serialized (quantity) SKUs alike. */
final class InventoryHealthQuery
{
    public function __construct(private readonly LowStockQuery $lowStock) {}

    /** @return array{total_skus: int, low_stock_count: int, out_of_stock_count: int} */
    public function forShop(?int $shopId): array
    {
        return [
            'total_skus' => SkuRecord::query()->count(),
            'low_stock_count' => count($this->lowStock->forShop($shopId)),
            'out_of_stock_count' => $this->lowStock->outOfStockCount($shopId),
        ];
    }
}
