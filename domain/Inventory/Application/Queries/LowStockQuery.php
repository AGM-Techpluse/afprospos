<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Queries;

use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryStockLevelRecord;

/**
 * INV-BR-07 / DBDD §27: evaluated against Available (on_hand -
 * reserved), never on_hand alone, so committed stock is never
 * reported as free stock.
 */
final class LowStockQuery
{
    /** @return array<int, array<string, mixed>> */
    public function forShop(?int $shopId): array
    {
        // whereColumn can't compare against a related table's column, and
        // low-stock lists are inherently small, so it's cheaper/clearer to
        // filter the in-memory result than force a raw cross-table join.
        $query = InventoryStockLevelRecord::query()->with(['sku.product']);

        if ($shopId !== null) {
            $query->where('shop_id', $shopId);
        }

        return $query->get()
            ->filter(function (InventoryStockLevelRecord $level): bool {
                $threshold = $level->sku->low_stock_threshold;

                return $threshold !== null && ($level->on_hand - $level->reserved) <= $threshold;
            })
            ->map(static fn (InventoryStockLevelRecord $level): array => [
                'stock_level_id' => $level->id,
                'sku_id' => $level->sku_id,
                'sku_code' => $level->sku->sku_code,
                'brand' => $level->sku->product->brand,
                'model' => $level->sku->product->model,
                'shop_id' => $level->shop_id,
                'on_hand' => $level->on_hand,
                'reserved' => $level->reserved,
                'available' => $level->on_hand - $level->reserved,
                'low_stock_threshold' => $level->sku->low_stock_threshold,
            ])
            ->values()
            ->all();
    }
}
