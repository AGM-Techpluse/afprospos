<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Queries;

use Domain\Inventory\Infrastructure\Persistence\Eloquent\ProductRecord;
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
    public function paginate(?string $search, ?string $category, ?string $brand, int $page, int $perPage = 20, ?int $shopId = null): array
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

        if ($shopId !== null) {
            // A serialized SKU never has a stockLevels row (quantity doesn't
            // apply to an individually-tracked unit) — filtering on
            // stockLevels alone would silently drop every serialized
            // product from a shop-filtered list even when units of it
            // exist at that shop, tracked instead via `items.current_shop_id`.
            $query->where(function ($shopFilter) use ($shopId): void {
                $shopFilter->whereHas('stockLevels', fn ($levelQuery) => $levelQuery->where('shop_id', $shopId))
                    ->orWhereHas('items', fn ($itemQuery) => $itemQuery->where('current_shop_id', $shopId));
            });
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

    /** Product-level lookup by its own id (not a Sku id) — backs the add-variant form, which only needs the Product's own identity, never any one Sku's pricing/stock. @return array{id:int, brand:string, model:string, category:string}|null */
    public function findProduct(int $productId): ?array
    {
        $product = ProductRecord::query()->find($productId);

        return $product !== null ? ['id' => $product->id, 'brand' => $product->brand, 'model' => $product->model, 'category' => $product->category] : null;
    }

    /** Backs the Products list's category filter — a dropdown of what actually exists, not free text (the create form stays free text; this is only for filtering by an already-typed value). @return string[] */
    public function categories(): array
    {
        return ProductRecord::query()
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->all();
    }

    /** @return array<string, mixed>|null */
    public function find(int $skuId): ?array
    {
        $sku = SkuRecord::query()->with(['product', 'stockLevels.sku', 'items'])->find($skuId);

        if ($sku === null) {
            return null;
        }

        $data = $this->toArray($sku, withStockBreakdown: true);
        $data['product_id'] = $sku->product_id;

        // INV-BR-17/18: other non-serialized variants of the same product
        // (e.g. a screenguard's other brands) — never itself, and never a
        // serialized sibling (a variant is a non-serialized concept only).
        $data['sibling_skus'] = SkuRecord::query()
            ->where('product_id', $sku->product_id)
            ->where('is_serialized', false)
            ->where('id', '!=', $sku->id)
            ->get()
            ->map(static fn (SkuRecord $sibling): array => [
                'id' => $sibling->id,
                'sku_code' => $sibling->sku_code,
                'attributes' => $sibling->attributes,
                'selling_price_minor' => $sibling->selling_price_minor,
            ])->all();

        return $data;
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

            // Serialized SKUs have no stockLevels row at all (quantity
            // doesn't apply to an individually-tracked unit) — each unit is
            // its own row instead, so list them directly rather than
            // leaving `stock_by_shop` empty and implying there's no stock.
            $data['serialized_units'] = $sku->items->map(static fn ($item): array => [
                'id' => $item->id,
                'imei' => $item->imei,
                'shop_id' => $item->current_shop_id,
                'condition' => $item->condition,
                'status' => $item->status,
            ])->all();

            // Business-wide totals (summed across every shop) for the
            // detail page's headline stat row (UI/UX §14C.8) — computed
            // once here from whichever source actually applies, so the
            // page shows one number regardless of is_serialized rather
            // than making the caller pick a branch.
            if ($sku->is_serialized) {
                $onHand = $sku->items->whereIn('status', ['available', 'reserved'])->count();
                $reserved = $sku->items->where('status', 'reserved')->count();
            } else {
                $onHand = (int) $sku->stockLevels->sum('on_hand');
                $reserved = (int) $sku->stockLevels->sum('reserved');
            }

            $data['on_hand'] = $onHand;
            $data['reserved'] = $reserved;
            $data['available'] = $onHand - $reserved;
        }

        return $data;
    }
}
