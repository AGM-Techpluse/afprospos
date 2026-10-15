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
     * The SKU for one exact configuration of a product (INV-BR-08/16/18). A
     * serialized device with the same attributes is the same SKU as the
     * previous one — its IMEIs are items under it, not new SKUs. Only an
     * identical signature matches, never a sibling variant.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function findByProductAndAttributes(ProductId $productId, array $attributes, bool $isSerialized): ?Sku;

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
        bool $isCommissionable = false,
        ?Money $commissionAmount = null,
    ): SkuId;

    public function save(Sku $sku): void;
}
