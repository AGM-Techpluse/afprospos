<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Services;

use Domain\Inventory\Domain\Exceptions\DuplicateSkuCode;
use Domain\Inventory\Domain\Exceptions\ImeiAlreadyExists;
use Domain\Inventory\Domain\Repositories\InventoryItemRepository;
use Domain\Inventory\Domain\Repositories\InventoryStockLevelRepository;
use Domain\Inventory\Domain\Repositories\SkuRepository;
use Domain\Inventory\Domain\Services\SkuGenerator;
use Domain\Inventory\Domain\ValueObjects\ProductId;
use Domain\Inventory\Domain\ValueObjects\SkuId;
use Domain\Shared\Domain\ValueObjects\Money;
use Domain\Shared\Domain\ValueObjects\ShopId;

/**
 * The single write path for "give this product a SKU (existing or new)
 * and receive stock against it" — extracted from CreateProductHandler
 * (INV-BR-17/18) so the single-item, multi-variant, and add-a-variant-
 * later flows all mint/restock a SKU identically instead of drifting.
 */
final class SkuVariantWriter
{
    public function __construct(
        private readonly SkuRepository $skus,
        private readonly InventoryItemRepository $items,
        private readonly InventoryStockLevelRepository $stockLevels,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @param  string[]|null  $initialImeis
     * @return array{skuId: SkuId, wasExisting: bool}
     */
    public function write(
        ProductId $productId,
        string $shopCode,
        string $category,
        array $attributes,
        bool $isSerialized,
        int $costPriceMinor,
        float $markupPercent,
        ?int $sellingPriceMinorOverride,
        ?int $lowStockThreshold,
        int $shopId,
        string $condition,
        ?int $initialQuantity,
        ?array $initialImeis,
    ): array {
        if (! $isSerialized) {
            $existingSku = $this->skus->findNonSerializedByProduct($productId, $attributes);

            if ($existingSku !== null) {
                $this->receiveInitialStock($existingSku->id(), $isSerialized, $shopId, $condition, $initialQuantity, $initialImeis);

                return ['skuId' => $existingSku->id(), 'wasExisting' => true];
            }
        }

        $sequence = $this->skus->nextSequence($shopCode, $category);
        $skuCode = SkuGenerator::generate($shopCode, $category, $sequence);

        if ($this->skus->existsWithCode($skuCode)) {
            throw DuplicateSkuCode::forCode($skuCode);
        }

        $costPrice = new Money($costPriceMinor);
        $sellingPriceOverridden = $sellingPriceMinorOverride !== null;
        $sellingPrice = $sellingPriceOverridden
            ? new Money($sellingPriceMinorOverride)
            : $costPrice->withMarkupPercent($markupPercent);

        $skuId = $this->skus->create(
            productId: $productId,
            skuCode: $skuCode,
            attributes: $attributes,
            isSerialized: $isSerialized,
            costPrice: $costPrice,
            markupPercent: $markupPercent,
            sellingPrice: $sellingPrice,
            sellingPriceOverridden: $sellingPriceOverridden,
            lowStockThreshold: $lowStockThreshold,
        );

        $this->receiveInitialStock($skuId, $isSerialized, $shopId, $condition, $initialQuantity, $initialImeis);

        return ['skuId' => $skuId, 'wasExisting' => false];
    }

    /** @param  string[]|null  $initialImeis */
    private function receiveInitialStock(SkuId $skuId, bool $isSerialized, int $shopId, string $condition, ?int $initialQuantity, ?array $initialImeis): void
    {
        $shop = new ShopId($shopId);

        if ($isSerialized) {
            foreach ($initialImeis ?? [] as $imei) {
                if ($this->items->existsWithImei($imei)) {
                    throw ImeiAlreadyExists::forImei($imei);
                }

                $this->items->create($skuId, $imei, $shop, $condition);
            }

            return;
        }

        if (($initialQuantity ?? 0) > 0) {
            $level = $this->stockLevels->lockForUpdate($skuId, $shop);
            $level->receive($initialQuantity);
            $this->stockLevels->save($level);
        }
    }
}
