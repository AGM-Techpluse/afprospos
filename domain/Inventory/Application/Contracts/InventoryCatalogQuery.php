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
     *     variant_label: string,
     *     is_commissionable: bool,
     *     commission_amount_minor: int|null,
     * }>
     */
    public function search(string $term, int $shopId, int $limit = 20, ?string $category = null, ?string $brand = null, ?string $model = null): array;

    /**
     * Brands of products that can be sold at the till — the checkout's brand filter.
     *
     * @return string[]
     */
    public function sellableBrands(): array;

    /**
     * Models of sellable products, optionally narrowed to a category and/or brand already
     * picked — the checkout's model (variant) filter, so a model with several colours or
     * storage sizes can be narrowed to before picking the exact unit.
     *
     * @return string[]
     */
    public function sellableModels(?string $category, ?string $brand): array;

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
     *     variant_label: string,
     *     is_commissionable: bool,
     *     commission_amount_minor: int|null,
     * }|null
     */
    public function find(int $skuId, int $shopId): ?array;
}
