<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Queries;

use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryStockLevelRecord;

/** Read-side query backing the Admin Stock list/adjust screens (non-serialized only — serialized units are browsed via ProductCatalogQuery's per-sku detail). */
final class StockLevelQuery
{
    /** @return array<string, mixed>|null */
    public function find(int $skuId, int $shopId): ?array
    {
        $level = InventoryStockLevelRecord::query()
            ->with('sku.product')
            ->where('sku_id', $skuId)
            ->where('shop_id', $shopId)
            ->first();

        if ($level === null) {
            return null;
        }

        return [
            'sku_id' => $level->sku_id,
            'sku_code' => $level->sku->sku_code,
            'brand' => $level->sku->product->brand,
            'model' => $level->sku->product->model,
            'shop_id' => $level->shop_id,
            'on_hand' => $level->on_hand,
            'reserved' => $level->reserved,
            'available' => $level->on_hand - $level->reserved,
        ];
    }

    /**
     * @return array{data: array<int, array<string, mixed>>, current_page:int, last_page:int, per_page:int, total:int}
     */
    public function paginate(?string $search, ?int $shopId, int $page, int $perPage = 20): array
    {
        $query = InventoryStockLevelRecord::query()->with(['sku.product']);

        if ($shopId !== null) {
            $query->where('shop_id', $shopId);
        }

        if ($search !== null && $search !== '') {
            $query->whereHas('sku', function ($skuQuery) use ($search): void {
                $skuQuery->where('sku_code', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($productQuery) use ($search): void {
                        $productQuery->where('brand', 'like', "%{$search}%")
                            ->orWhere('model', 'like', "%{$search}%");
                    });
            });
        }

        $paginator = $query->orderBy('id')->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $paginator->getCollection()->map(static fn (InventoryStockLevelRecord $level): array => [
                'id' => $level->id,
                'sku_id' => $level->sku_id,
                'sku_code' => $level->sku->sku_code,
                'brand' => $level->sku->product->brand,
                'model' => $level->sku->product->model,
                'shop_id' => $level->shop_id,
                'on_hand' => $level->on_hand,
                'reserved' => $level->reserved,
                'available' => $level->on_hand - $level->reserved,
            ])->all(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}
