<?php

declare(strict_types=1);

namespace Domain\Inventory\Domain\Entities;

use Domain\Inventory\Domain\ValueObjects\ProductId;
use Domain\Inventory\Domain\ValueObjects\SkuId;
use Domain\Shared\Domain\ValueObjects\Money;

/**
 * A specific sellable configuration ("iPhone 15/128GB/Black"), defined
 * once at the business level (BLD §9.1, INV-BR-08) — never per-shop.
 */
final class Sku
{
    private function __construct(
        private readonly SkuId $id,
        private readonly ProductId $productId,
        private readonly string $skuCode,
        private array $attributes,
        private readonly bool $isSerialized,
        private Money $costPrice,
        private float $markupPercent,
        private Money $sellingPrice,
        private bool $sellingPriceOverridden,
        private ?int $lowStockThreshold,
    ) {}

    /** @param  array<string, mixed>  $attributes */
    public static function reconstitute(
        SkuId $id,
        ProductId $productId,
        string $skuCode,
        array $attributes,
        bool $isSerialized,
        Money $costPrice,
        float $markupPercent,
        Money $sellingPrice,
        bool $sellingPriceOverridden,
        ?int $lowStockThreshold,
    ): self {
        return new self(
            $id,
            $productId,
            $skuCode,
            $attributes,
            $isSerialized,
            $costPrice,
            $markupPercent,
            $sellingPrice,
            $sellingPriceOverridden,
            $lowStockThreshold,
        );
    }

    /**
     * BRD INV-09: auto-calculate selling price from cost + markup,
     * unless a manual override is in effect.
     */
    public static function calculateSellingPrice(Money $costPrice, float $markupPercent): Money
    {
        return $costPrice->withMarkupPercent($markupPercent);
    }

    public function overrideSellingPrice(Money $price): void
    {
        $this->sellingPrice = $price;
        $this->sellingPriceOverridden = true;
    }

    public function id(): SkuId
    {
        return $this->id;
    }

    public function productId(): ProductId
    {
        return $this->productId;
    }

    public function skuCode(): string
    {
        return $this->skuCode;
    }

    public function isSerialized(): bool
    {
        return $this->isSerialized;
    }

    public function costPrice(): Money
    {
        return $this->costPrice;
    }

    public function markupPercent(): float
    {
        return $this->markupPercent;
    }

    public function sellingPrice(): Money
    {
        return $this->sellingPrice;
    }

    public function sellingPriceOverridden(): bool
    {
        return $this->sellingPriceOverridden;
    }

    public function lowStockThreshold(): ?int
    {
        return $this->lowStockThreshold;
    }

    /** @return array<string, mixed> */
    public function attributes(): array
    {
        return $this->attributes;
    }
}
