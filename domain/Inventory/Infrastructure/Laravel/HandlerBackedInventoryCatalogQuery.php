<?php

declare(strict_types=1);

namespace Domain\Inventory\Infrastructure\Laravel;

use Domain\Inventory\Application\Contracts\InventoryCatalogQuery;
use Domain\Inventory\Domain\Services\StockAvailability;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryItemRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryStockLevelRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;

/** The Contract's implementation — Eloquent is permitted here (Infrastructure query layer, CPNC §2.1), same as ProductCatalogQuery/StockLevelQuery it reuses underneath. */
final class HandlerBackedInventoryCatalogQuery implements InventoryCatalogQuery
{
    public function search(string $term, int $shopId, int $limit = 20): array
    {
        $skus = SkuRecord::query()
            ->with('product')
            ->where(function ($query) use ($term): void {
                $query->where('sku_code', 'like', "%{$term}%")
                    ->orWhereHas('product', function ($productQuery) use ($term): void {
                        $productQuery->where('brand', 'like', "%{$term}%")
                            ->orWhere('model', 'like', "%{$term}%");
                    });
            })
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
        ])->all();
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
