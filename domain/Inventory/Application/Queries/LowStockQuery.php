<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Queries;

use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryItemRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryStockLevelRecord;
use Illuminate\Support\Collection;

/**
 * INV-BR-07 / DBDD §27: evaluated against Available (on_hand -
 * reserved), never on_hand alone, so committed stock is never
 * reported as free stock.
 *
 * A serialized SKU never has an `inventory_stock_levels` row -- quantity
 * doesn't apply to an individually-tracked unit, so its "on hand" is
 * derived by counting `inventory_items` rows per (sku, shop) instead
 * (`available`/`reserved` statuses count as on-hand; `sold`/`transferring`
 * don't). Both branches share the exact same threshold rule so a
 * serialized SKU running low reads no differently from a non-serialized
 * one.
 */
final class LowStockQuery
{
    /**
     * @param  int[]|null  $restrictToShopIds
     * @return array<int, array<string, mixed>>
     */
    public function forShop(?int $shopId, ?array $restrictToShopIds = null): array
    {
        $nonSerialized = $this->nonSerializedSummaries($shopId, $restrictToShopIds)
            ->filter(fn (array $row): bool => $row['low_stock_threshold'] !== null && $row['available'] <= $row['low_stock_threshold']);

        $serialized = $this->serializedSummaries($shopId, $restrictToShopIds)
            ->filter(fn (array $row): bool => $row['low_stock_threshold'] !== null && $row['available'] <= $row['low_stock_threshold']);

        return $nonSerialized->concat($serialized)->values()->all();
    }

    /**
     * No single SQL query spans both the non-serialized (`inventory_stock_levels`)
     * and serialized (`inventory_items`, grouped) sources this list merges in
     * memory — pagination/search is applied to the already-merged PHP array
     * rather than forcing a cross-source SQL UNION. Low-stock lists are
     * inherently small (tens, not thousands, of distinct SKUs per shop), so
     * this stays cheap.
     *
     * @param  int[]|null  $restrictToShopIds
     * @return array{data: array<int, array<string, mixed>>, current_page:int, last_page:int, per_page:int, total:int}
     */
    public function paginate(?int $shopId, ?string $search, int $page, int $perPage = 20, ?array $restrictToShopIds = null): array
    {
        $rows = collect($this->forShop($shopId, $restrictToShopIds));

        if ($search !== null && $search !== '') {
            $needle = mb_strtolower($search);
            $rows = $rows->filter(fn (array $row): bool => str_contains(mb_strtolower("{$row['brand']} {$row['model']} {$row['sku_code']}"), $needle));
        }

        $rows = $rows->sortBy('available')->values();
        $total = $rows->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));

        return [
            'data' => $rows->slice(($page - 1) * $perPage, $perPage)->values()->all(),
            'current_page' => $page,
            'last_page' => $lastPage,
            'per_page' => $perPage,
            'total' => $total,
        ];
    }

    /** Every out-of-stock (sku, shop) pair, serialized or not, regardless of whether a low_stock_threshold is even configured -- mirrors the non-serialized branch's original "on_hand <= reserved" rule with no threshold requirement. */
    public function outOfStockCount(?int $shopId): int
    {
        return count($this->outOfStock($shopId));
    }

    /** @return array<int, array<string, mixed>> */
    public function outOfStock(?int $shopId): array
    {
        $nonSerialized = $this->nonSerializedSummaries($shopId)->filter(fn (array $row): bool => $row['available'] <= 0);
        $serialized = $this->serializedSummaries($shopId)->filter(fn (array $row): bool => $row['available'] <= 0);

        return $nonSerialized->concat($serialized)->values()->all();
    }

    /**
     * whereColumn can't compare against a related table's column, and
     * these lists are inherently small, so it's cheaper/clearer to filter
     * the in-memory result than force a raw cross-table join.
     *
     * @param  int[]|null  $restrictToShopIds
     * @return Collection<int, array<string, mixed>>
     */
    private function nonSerializedSummaries(?int $shopId, ?array $restrictToShopIds = null): Collection
    {
        $query = InventoryStockLevelRecord::query()->with(['sku.product']);

        if ($shopId !== null) {
            $query->where('shop_id', $shopId);
        } elseif ($restrictToShopIds !== null) {
            $query->whereIn('shop_id', $restrictToShopIds);
        }

        return $query->get()->map(static fn (InventoryStockLevelRecord $level): array => [
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
            'is_serialized' => false,
        ]);
    }

    /**
     * @param  int[]|null  $restrictToShopIds
     * @return Collection<int, array<string, mixed>>
     */
    private function serializedSummaries(?int $shopId, ?array $restrictToShopIds = null): Collection
    {
        $query = InventoryItemRecord::query()->with(['sku.product'])->whereHas('sku', fn ($skuQuery) => $skuQuery->where('is_serialized', true));

        if ($shopId !== null) {
            $query->where('current_shop_id', $shopId);
        } elseif ($restrictToShopIds !== null) {
            $query->whereIn('current_shop_id', $restrictToShopIds);
        }

        return $query->get()
            ->groupBy(fn (InventoryItemRecord $item): string => "{$item->sku_id}-{$item->current_shop_id}")
            ->map(function ($items): array {
                /** @var InventoryItemRecord $first */
                $first = $items->first();
                $onHand = $items->whereIn('status', ['available', 'reserved'])->count();
                $reserved = $items->where('status', 'reserved')->count();

                return [
                    'stock_level_id' => null,
                    'sku_id' => $first->sku_id,
                    'sku_code' => $first->sku->sku_code,
                    'brand' => $first->sku->product->brand,
                    'model' => $first->sku->product->model,
                    'shop_id' => $first->current_shop_id,
                    'on_hand' => $onHand,
                    'reserved' => $reserved,
                    'available' => $onHand - $reserved,
                    'low_stock_threshold' => $first->sku->low_stock_threshold,
                    'is_serialized' => true,
                ];
            })
            ->values();
    }
}
