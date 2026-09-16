<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Queries;

use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryItemRecord;

/**
 * Read-side query backing the Admin "Serialized units" browse page — the
 * IMEI-tracked equivalent of StockLevelQuery's non-serialized stock list.
 * A serialized SKU has no quantity anywhere; each row here is one
 * physical unit, so this is the only place an owner can actually browse
 * "which specific devices do we have, and where."
 */
final class SerializedUnitsQuery
{
    /**
     * @return array{data: array<int, array<string, mixed>>, current_page:int, last_page:int, per_page:int, total:int}
     */
    public function paginate(?string $search, ?int $shopId, ?string $status, int $page, int $perPage = 20): array
    {
        $query = InventoryItemRecord::query()->with(['sku.product'])->orderByDesc('id');

        if ($search !== null && $search !== '') {
            $query->where(function ($inner) use ($search): void {
                $inner->where('imei', 'like', "%{$search}%")
                    ->orWhereHas('sku', function ($skuQuery) use ($search): void {
                        $skuQuery->where('sku_code', 'like', "%{$search}%")
                            ->orWhereHas('product', function ($productQuery) use ($search): void {
                                $productQuery->where('brand', 'like', "%{$search}%")
                                    ->orWhere('model', 'like', "%{$search}%");
                            });
                    });
            });
        }

        if ($shopId !== null) {
            $query->where('current_shop_id', $shopId);
        }

        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $paginator->getCollection()->map(static fn (InventoryItemRecord $item): array => [
                'id' => $item->id,
                'imei' => $item->imei,
                'sku_id' => $item->sku_id,
                'sku_code' => $item->sku->sku_code,
                'brand' => $item->sku->product->brand,
                'model' => $item->sku->product->model,
                'shop_id' => $item->current_shop_id,
                'condition' => $item->condition,
                'status' => $item->status,
            ])->all(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}
