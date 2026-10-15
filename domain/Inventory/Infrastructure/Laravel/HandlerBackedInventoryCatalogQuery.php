<?php

declare(strict_types=1);

namespace Domain\Inventory\Infrastructure\Laravel;

use Domain\Inventory\Application\Contracts\InventoryCatalogQuery;
use Domain\Inventory\Domain\Services\StockAvailability;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryItemRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryStockLevelRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\ProductRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;

/** The Contract's implementation — Eloquent is permitted here (Infrastructure query layer, CPNC §2.1), same as ProductCatalogQuery/StockLevelQuery it reuses underneath. */
final class HandlerBackedInventoryCatalogQuery implements InventoryCatalogQuery
{
    public function search(string $term, int $shopId, int $limit = 20, ?string $category = null, ?string $brand = null, ?string $model = null): array
    {
        $skus = SkuRecord::query()
            ->with('product')
            // Repair parts are never sold at the till: only products meant for sale reach checkout search.
            ->whereHas('product', fn ($productQuery) => $productQuery->whereIn('purpose', ['sale', 'both']))
            ->when($term !== '', function ($query) use ($term): void {
                $query->where(function ($inner) use ($term): void {
                    $inner->where('sku_code', 'like', "%{$term}%")
                        ->orWhereHas('items', fn ($item) => $item->where('imei', $term))
                        ->orWhereHas('product', function ($productQuery) use ($term): void {
                            $productQuery->where('brand', 'like', "%{$term}%")
                                ->orWhere('model', 'like', "%{$term}%");
                        });
                });
            })
            ->when($category !== null && $category !== '', fn ($query) => $query->whereHas('product', fn ($productQuery) => $productQuery->where('category', $category)))
            ->when($brand !== null && $brand !== '', fn ($query) => $query->whereHas('product', fn ($productQuery) => $productQuery->where('brand', $brand)))
            ->when($model !== null && $model !== '', fn ($query) => $query->whereHas('product', fn ($productQuery) => $productQuery->where('model', $model)))
            ->orderBy('sku_code')
            ->limit($limit)
            ->get();

        return $skus->map(fn (SkuRecord $sku): array => [
            'sku_id' => $sku->id,
            'sku_code' => $sku->sku_code,
            'product_name' => trim("{$sku->product->brand} {$sku->product->model}"),
            'is_serialized' => (bool) $sku->is_serialized,
            'selling_price_minor' => $sku->selling_price_minor,
            'available' => $this->available($sku, $shopId),
            'variant_label' => $this->variantLabel($sku),
            'is_commissionable' => (bool) $sku->is_commissionable,
            'commission_amount_minor' => $sku->commission_amount_minor,
        ])->all();
    }

    public function sellableModels(?string $category, ?string $brand): array
    {
        return ProductRecord::query()
            ->whereIn('purpose', ['sale', 'both'])
            ->when($category !== null && $category !== '', fn ($query) => $query->where('category', $category))
            ->when($brand !== null && $brand !== '', fn ($query) => $query->where('brand', $brand))
            ->distinct()
            ->orderBy('model')
            ->pluck('model')
            ->all();
    }

    /**
     * A short description of what makes this SKU's physical units distinct from any
     * sibling variant of the same product — colour, material, compatibility, then any
     * free-form attribute (e.g. storage). Empty for a product with no variants.
     */
    private function variantLabel(SkuRecord $sku): string
    {
        $attributes = $sku->attributes ?? [];
        $parts = [];

        foreach (['color', 'material', 'compatibility'] as $key) {
            if (! empty($attributes[$key])) {
                $parts[] = (string) $attributes[$key];
            }
        }

        foreach ((array) ($attributes['custom'] ?? []) as $value) {
            if ($value !== null && $value !== '') {
                $parts[] = (string) $value;
            }
        }

        return implode(' · ', $parts);
    }

    public function sellableBrands(): array
    {
        return ProductRecord::query()
            ->whereIn('purpose', ['sale', 'both'])
            ->distinct()
            ->orderBy('brand')
            ->pluck('brand')
            ->all();
    }

    public function find(int $skuId, int $shopId): ?array
    {
        $sku = SkuRecord::query()->with('product')->find($skuId);

        if ($sku === null) {
            return null;
        }

        return [
            'sku_id' => $sku->id,
            'sku_code' => $sku->sku_code,
            'product_name' => trim("{$sku->product->brand} {$sku->product->model}"),
            'is_serialized' => (bool) $sku->is_serialized,
            'selling_price_minor' => $sku->selling_price_minor,
            'available' => $this->available($sku, $shopId),
            'variant_label' => $this->variantLabel($sku),
            'is_commissionable' => (bool) $sku->is_commissionable,
            'commission_amount_minor' => $sku->commission_amount_minor,
        ];
    }

    private function available(SkuRecord $sku, int $shopId): int
    {
        if ($sku->is_serialized) {
            return InventoryItemRecord::query()
                ->where('sku_id', $sku->id)
                ->where('current_shop_id', $shopId)
                ->where('status', 'available')
                ->count();
        }

        $level = InventoryStockLevelRecord::query()
            ->where('sku_id', $sku->id)
            ->where('shop_id', $shopId)
            ->first();

        return StockAvailability::available($level->on_hand ?? 0, $level->reserved ?? 0);
    }
}
