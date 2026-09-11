<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Queries;

use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;

/**
 * Read-side query backing the Admin Products list/show screens
 * (BRD INV-13: searchable, filterable by category/brand). Eloquent is
 * permitted here — this is the Infrastructure query layer (CPNC §2.1).
 */
final class ProductCatalogQuery
{
    /**
     * @return array{data: array<int, array<string, mixed>>, current_page:int, last_page:int, per_page:int, total:int}
     */
    public function paginate(?string $search, ?string $category, ?string $brand, int $page, int $perPage = 20): array
    {
        $query = SkuRecord::query()->with('product')->orderBy('sku_code');

        if ($search !== null && $search !== '') {
            $query->where(function ($inner) use ($search): void {
                $inner->where('sku_code', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($productQuery) use ($search): void {
                        $productQuery->where('brand', 'like', "%{$search}%")
                            ->orWhere('model', 'like', "%{$search}%");
                    });
            });
        }

        if ($category !== null && $category !== '') {
            $query->whereHas('product', fn ($productQuery) => $productQuery->where('category', $category));
        }

        if ($brand !== null && $brand !== '') {
            $query->whereHas('product', fn ($productQuery) => $productQuery->where('brand', $brand));
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $paginator->getCollection()->map(fn (SkuRecord $sku): array => $this->toArray($sku))->all(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    /** @return array<string, mixed>|null */
    public function find(int $skuId): ?array
    {
        $sku = SkuRecord::query()->with(['product', 'stockLevels.sku'])->find($skuId);

        return $sku !== null ? $this->toArray($sku, withStockBreakdown: true) : null;
    }

    /** @return array<string, mixed> */
    private function toArray(SkuRecord $sku, bool $withStockBreakdown = false): array
    {
        $data = [
            'id' => $sku->id,
            'sku_code' => $sku->sku_code,
            'brand' => $sku->product->brand,
            'model' => $sku->product->model,
            'category' => $sku->product->category,
            'attributes' => $sku->attributes,
            'is_serialized' => $sku->is_serialized,
            'cost_price_minor' => $sku->cost_price_minor,
            'markup_percent' => (float) $sku->markup_percent,
            'selling_price_minor' => $sku->selling_price_minor,
            'selling_price_overridden' => $sku->selling_price_overridden,
            'low_stock_threshold' => $sku->low_stock_threshold,
        ];

        if ($withStockBreakdown) {
            $data['stock_by_shop'] = $sku->stockLevels->map(static fn ($level): array => [
                'shop_id' => $level->shop_id,
                'on_hand' => $level->on_hand,
                'reserved' => $level->reserved,
                'available' => $level->on_hand - $level->reserved,
            ])->all();
        }

        return $data;
    }
}
