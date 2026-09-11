<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Contracts;

/**
 * The published cross-module read contract (mirrors
 * InventoryReservationService's write-side role): Sales' POS cart
 * searches the catalog and checks per-shop availability through this,
 * never by reaching into Inventory's own Query classes directly
 * (CPNC §4.3's `PaymentQuery` example is the sanctioned pattern for
 * exactly this kind of cross-module read).
 */
interface InventoryCatalogQuery
{
    /**
     * @return array<int, array{
     *     sku_id: int,
     *     sku_code: string,
     *     product_name: string,
     *     is_serialized: bool,
     *     selling_price_minor: int,
     *     available: int,
     * }>
     */
    public function search(string $term, int $shopId, int $limit = 20): array;

    /**
     * Single-SKU lookup — used at checkout-item-add time to snapshot
     * price/serialization without the Handler depending on Inventory's
     * Domain repositories directly.
     *
     * @return array{
     *     sku_id: int,
     *     sku_code: string,
     *     product_name: string,
     *     is_serialized: bool,
     *     selling_price_minor: int,
     *     available: int,
     * }|null
     */
    public function find(int $skuId, int $shopId): ?array;
}
