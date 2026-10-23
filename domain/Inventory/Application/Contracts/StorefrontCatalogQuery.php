<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Contracts;

/**
 * The published read contract behind the public storefront (`/shop`,
 * `/shop/{sku}`) — shop-agnostic (no `$shopId` param, unlike
 * `InventoryCatalogQuery`) and customer-safe (no cost price/markup).
 * Products are already global, not shop-scoped (DBDD: "the centralized
 * product catalog is distinct from shop stock"), so this is purely a
 * different shape over the same data, not a different data source.
 */
interface StorefrontCatalogQuery
{
    /**
     * Only `purpose IN ('sale','both')` products ever appear here — a
     * repair-only part is never offered for sale.
     *
     * @return array{data: array<int, array<string, mixed>>, current_page:int, last_page:int, per_page:int, total:int}
     */
    public function list(?string $search, ?string $category, int $page, int $perPage = 24): array;

    /**
     * The storefront homepage's "Featured products" row — the newest
     * sellable products first, flat (no pagination envelope).
     *
     * @return array<int, array<string, mixed>>
     */
    public function featured(int $limit = 8): array;

    /**
     * Includes a `siblings` entry (other SKUs of the same product — the
     * color/storage/etc. variant switcher on the detail page), absent
     * from `list()`/`featured()`'s rows since only the detail page needs it.
     *
     * @return array<string, mixed>|null
     */
    public function find(int $skuId): ?array;

    /** @return string[] */
    public function categories(): array;
}
