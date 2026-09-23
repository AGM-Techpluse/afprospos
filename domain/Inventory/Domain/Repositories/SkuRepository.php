<?php

declare(strict_types=1);

namespace Domain\Inventory\Domain\Repositories;

use Domain\Inventory\Domain\Entities\Sku;
use Domain\Inventory\Domain\ValueObjects\ProductId;
use Domain\Inventory\Domain\ValueObjects\SkuId;
use Domain\Shared\Domain\ValueObjects\Money;

interface SkuRepository
{
    public function get(SkuId $id): Sku;

    public function existsWithCode(string $skuCode): bool;

    /**
     * BRD INV-01 (restock): re-adding/re-importing the same brand+model+
     * category+attributes reuses the existing SKU rather than minting a
     * duplicate. `$attributes` disambiguates between variants (INV-BR-16)
     * — a product with several non-serialized SKUs (e.g. screenguard
     * brands) matches only the one whose attributes are identical, never
     * an arbitrary sibling variant.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function findNonSerializedByProduct(ProductId $productId, array $attributes): ?Sku;

    /** @return Sku[] every non-serialized SKU for this product, for listing a product's variants (INV-BR-17/18). */
    public function findAllNonSerializedByProduct(ProductId $productId): array;

    /** DBDD §14.2 SKU format `[ShopCode]-[CategoryCode]-[Sequence]` — the next unused sequence for this shop+category pair. */
    public function nextSequence(string $shopCode, string $category): int;

    /** @param  array<string, mixed>  $attributes */
    public function create(
        ProductId $productId,
        string $skuCode,
        array $attributes,
        bool $isSerialized,
        Money $costPrice,
        float $markupPercent,
        Money $sellingPrice,
        bool $sellingPriceOverridden,
        ?int $lowStockThreshold,
    ): SkuId;

    public function save(Sku $sku): void;
}
