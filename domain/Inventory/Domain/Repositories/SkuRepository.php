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

    /** BRD INV-01 (restock): a non-serialized product has exactly one business-wide SKU — re-adding/re-importing the same brand+model+category reuses it rather than minting a duplicate. */
    public function findNonSerializedByProduct(ProductId $productId): ?Sku;

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
