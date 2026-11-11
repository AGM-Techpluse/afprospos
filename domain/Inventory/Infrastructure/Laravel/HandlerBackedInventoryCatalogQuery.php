<?php

declare(strict_types=1);

namespace Domain\Inventory\Infrastructure\Laravel;

use Domain\Inventory\Application\Contracts\InventoryCatalogQuery;
use Domain\Inventory\Domain\Services\SkuVariantLabel;
use Domain\Inventory\Domain\Services\StockAvailability;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryItemRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryStockLevelRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\ProductRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;

/** The Contract's implementation — Eloquent is permitted here (Infrastructure query layer, CPNC §2.1), same as ProductCatalogQuery/StockLevelQuery it reuses underneath. */
final class HandlerBackedInventoryCatalogQuery implements InventoryCatalogQuery
{
    public function search(string $term, int $shopId, int $limit = 20, ?string $category = null, ?string $brand = null, ?string $model = null, array $purposes = ['sale', 'both']): array
    {
        $skus = SkuRecord::query()
            ->with('product')
            ->whereHas('product', fn ($productQuery) => $productQuery->whereIn('purpose', $purposes))
            ->when($term !== '', function ($query) use ($term): void {
                $query->where(function ($inner) use ($term): void {
                    $inner->where('sku_code', 'like', "%{$term}%")
                        ->orWhereHas('items', fn ($item) => $item->where('imei', $term))
                        ->orWhereHas('product', function ($productQuery) use ($term): void {
                            $productQuery->where('brand', 'like', "%{$term}%")
                                ->orWhere('model', 'like', "%{$term}%");
                        })
                        ->orWhere('attributes', 'like', "%{$term}%");
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
            'brand' => $sku->product->brand,
            'model' => $sku->product->model,
            'category' => $sku->product->category,
            'description' => $sku->product->description,
            'attributes' => $sku->attributes ?? [],
            'is_commissionable' => (bool) $sku->is_commissionable,
            'commission_amount_minor' => $sku->commission_amount_minor,
            'image_url' => $this->imageUrl($sku),
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
        return SkuVariantLabel::fromAttributes($sku->attributes ?? []);
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
            'brand' => $sku->product->brand,
            'model' => $sku->product->model,
            'category' => $sku->product->category,
            'description' => $sku->product->description,
            'attributes' => $sku->attributes ?? [],
            'is_commissionable' => (bool) $sku->is_commissionable,
            'commission_amount_minor' => $sku->commission_amount_minor,
            'image_url' => $this->imageUrl($sku),
        ];
    }

    public function findMany(array $skuIds, int $shopId): array
    {
        if ($skuIds === []) {
            return [];
        }

        $skus = SkuRecord::query()->with('product')->whereIn('id', $skuIds)->get();

        $serializedIds = $skus->filter(fn (SkuRecord $sku): bool => (bool) $sku->is_serialized)->pluck('id');
        $nonSerializedIds = $skus->filter(fn (SkuRecord $sku): bool => ! $sku->is_serialized)->pluck('id');

        $availableCountsBySerializedSku = InventoryItemRecord::query()
            ->whereIn('sku_id', $serializedIds)
            ->where('current_shop_id', $shopId)
            ->where('status', 'available')
            ->selectRaw('sku_id, count(*) as available_count')
            ->groupBy('sku_id')
            ->pluck('available_count', 'sku_id');

        $stockLevelsBySku = InventoryStockLevelRecord::query()
            ->whereIn('sku_id', $nonSerializedIds)
            ->where('shop_id', $shopId)
            ->get()
            ->keyBy('sku_id');

        return $skus->mapWithKeys(function (SkuRecord $sku) use ($availableCountsBySerializedSku, $stockLevelsBySku): array {
            if ($sku->is_serialized) {
                $available = (int) ($availableCountsBySerializedSku[$sku->id] ?? 0);
            } else {
                $level = $stockLevelsBySku[$sku->id] ?? null;
                $available = StockAvailability::available($level->on_hand ?? 0, $level->reserved ?? 0);
            }

            return [$sku->id => [
                'sku_id' => $sku->id,
                'sku_code' => $sku->sku_code,
                'product_name' => trim("{$sku->product->brand} {$sku->product->model}"),
                'is_serialized' => (bool) $sku->is_serialized,
                'selling_price_minor' => $sku->selling_price_minor,
                'available' => $available,
                'variant_label' => $this->variantLabel($sku),
                'brand' => $sku->product->brand,
                'model' => $sku->product->model,
                'category' => $sku->product->category,
                'description' => $sku->product->description,
                'attributes' => $sku->attributes ?? [],
                'is_commissionable' => (bool) $sku->is_commissionable,
                'commission_amount_minor' => $sku->commission_amount_minor,
                'image_url' => $this->imageUrl($sku),
            ]];
        })->all();
    }

    public function findBySkuCode(string $skuCode): ?array
    {
        $sku = SkuRecord::query()->with('product')->where('sku_code', $skuCode)->first();

        if ($sku === null) {
            return null;
        }

        return [
            'sku_id' => $sku->id,
            'sku_code' => $sku->sku_code,
            'product_name' => trim("{$sku->product->brand} {$sku->product->model}"),
        ];
    }

    private function imageUrl(SkuRecord $sku): ?string
    {
        return $sku->product->image_path !== null ? route('admin.inventory.products.image', ['product' => $sku->id]) : null;
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
