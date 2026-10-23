<?php

declare(strict_types=1);

namespace Domain\Inventory\Infrastructure\Laravel;

use Domain\Inventory\Application\Contracts\StorefrontCatalogQuery;
use Domain\Inventory\Domain\Services\StockAvailability;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryItemRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryStockLevelRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\ProductRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Illuminate\Support\Collection;

/** The Contract's implementation — Eloquent is permitted here (Infrastructure query layer, CPNC §2.1). */
final class HandlerBackedStorefrontCatalogQuery implements StorefrontCatalogQuery
{
    public function list(?string $search, ?string $category, int $page, int $perPage = 24): array
    {
        $query = SkuRecord::query()
            ->with('product')
            ->whereHas('product', fn ($productQuery) => $productQuery->whereIn('purpose', ['sale', 'both']))
            ->when($search !== null && $search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('sku_code', 'like', "%{$search}%")
                        ->orWhereHas('product', function ($productQuery) use ($search): void {
                            $productQuery->where('brand', 'like', "%{$search}%")
                                ->orWhere('model', 'like', "%{$search}%");
                        });
                });
            })
            ->when($category !== null && $category !== '', fn ($query) => $query->whereHas('product', fn ($productQuery) => $productQuery->where('category', $category)))
            ->orderBy('sku_code');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);
        $shops = $this->pickupShops();

        return [
            'data' => $paginator->getCollection()->map(fn (SkuRecord $sku): array => $this->toArray($sku, $shops))->all(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    public function featured(int $limit = 8): array
    {
        $orderedIds = SkuRecord::query()
            ->join('inventory_products', 'inventory_skus.product_id', '=', 'inventory_products.id')
            ->whereIn('inventory_products.purpose', ['sale', 'both'])
            ->orderByDesc('inventory_products.created_at')
            ->orderBy('inventory_skus.sku_code')
            ->limit($limit)
            ->pluck('inventory_skus.id');

        $skusById = SkuRecord::query()->with('product')->whereIn('id', $orderedIds)->get()->keyBy('id');
        $shops = $this->pickupShops();

        return $orderedIds
            ->map(fn (int $id): array => $this->toArray($skusById[$id], $shops))
            ->all();
    }

    public function find(int $skuId): ?array
    {
        $sku = SkuRecord::query()
            ->with('product')
            ->whereHas('product', fn ($productQuery) => $productQuery->whereIn('purpose', ['sale', 'both']))
            ->find($skuId);

        if ($sku === null) {
            return null;
        }

        $shops = $this->pickupShops();
        $data = $this->toArray($sku, $shops);
        $data['siblings'] = $this->siblingVariants($sku, $shops);

        return $data;
    }

    public function categories(): array
    {
        return ProductRecord::query()
            ->whereIn('purpose', ['sale', 'both'])
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->all();
    }

    /** Active, non-warehouse shops only — a customer can never pick up from a warehouse (mirrors CreateCheckoutHandler's WarehouseCannotSell guard) or an inactive shop. */
    private function pickupShops(): Collection
    {
        return ShopRecord::query()
            ->where('status', 'active')
            ->where('is_warehouse', false)
            ->get(['id', 'name'])
            ->keyBy('id');
    }

    /** @return array<string, mixed> */
    private function toArray(SkuRecord $sku, Collection $shops): array
    {
        $availability = $this->availabilityByShop($sku, $shops);

        return [
            'sku_id' => $sku->id,
            'sku_code' => $sku->sku_code,
            'brand' => $sku->product->brand,
            'model' => $sku->product->model,
            'category' => $sku->product->category,
            'description' => $sku->product->description,
            'image_url' => $sku->product->image_path !== null ? route('admin.inventory.products.image', ['product' => $sku->id]) : null,
            'attributes' => $sku->attributes,
            'is_serialized' => $sku->is_serialized,
            'selling_price_minor' => $sku->selling_price_minor,
            'available_total' => array_sum($availability),
            'shops_available' => collect($availability)
                ->map(fn (int $available, int $shopId): array => ['shop_id' => $shopId, 'shop_name' => $shops[$shopId]->name, 'available' => $available])
                ->values()
                ->all(),
        ];
    }

    /**
     * Other SKUs of the same product (color/storage/etc. variants) — lets
     * the detail page offer a variant switcher instead of silently
     * hiding that other options of this same product exist.
     *
     * @return array<int, array<string, mixed>>
     */
    private function siblingVariants(SkuRecord $sku, Collection $shops): array
    {
        return SkuRecord::query()
            ->where('product_id', $sku->product_id)
            ->where('id', '!=', $sku->id)
            ->get()
            ->map(function (SkuRecord $sibling) use ($shops): array {
                $availability = $this->availabilityByShop($sibling, $shops);

                return [
                    'sku_id' => $sibling->id,
                    'sku_code' => $sibling->sku_code,
                    'attributes' => $sibling->attributes,
                    'selling_price_minor' => $sibling->selling_price_minor,
                    'available_total' => array_sum($availability),
                ];
            })
            ->all();
    }

    /** @return array<int, int> shop_id => available, only shops with at least one unit. */
    private function availabilityByShop(SkuRecord $sku, Collection $shops): array
    {
        if ($sku->is_serialized) {
            $counts = InventoryItemRecord::query()
                ->where('sku_id', $sku->id)
                ->where('status', 'available')
                ->whereIn('current_shop_id', $shops->keys())
                ->selectRaw('current_shop_id, count(*) as total')
                ->groupBy('current_shop_id')
                ->pluck('total', 'current_shop_id');
        } else {
            $counts = InventoryStockLevelRecord::query()
                ->where('sku_id', $sku->id)
                ->whereIn('shop_id', $shops->keys())
                ->get(['shop_id', 'on_hand', 'reserved'])
                ->mapWithKeys(fn (InventoryStockLevelRecord $level): array => [$level->shop_id => StockAvailability::available($level->on_hand, $level->reserved)]);
        }

        return $counts->filter(fn (int $available): bool => $available > 0)->map(fn ($value): int => (int) $value)->all();
    }
}
