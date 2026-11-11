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
        private bool $isCommissionable,
        private ?Money $commissionAmount,
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
        bool $isCommissionable = false,
        ?Money $commissionAmount = null,
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
            $isCommissionable,
            $commissionAmount,
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

    /**
     * Correcting a cost/markup entry mistake after creation (e.g. a wrong
     * cost price at intake). Recomputes the auto-calculated selling price
     * from the new inputs unless a manual override is already in effect —
     * same precedence BRD INV-09 establishes at creation time.
     */
    public function updateCostAndMarkup(Money $costPrice, float $markupPercent): void
    {
        $this->costPrice = $costPrice;
        $this->markupPercent = $markupPercent;

        if (! $this->sellingPriceOverridden) {
            $this->sellingPrice = self::calculateSellingPrice($costPrice, $markupPercent);
        }
    }

    /** Reverts to the auto-calculated selling price, discarding any manual override. */
    public function clearSellingPriceOverride(): void
    {
        $this->sellingPriceOverridden = false;
        $this->sellingPrice = self::calculateSellingPrice($this->costPrice, $this->markupPercent);
    }

    /** The flat staff commission paid when this SKU is sold, earned by the cashier at sale finalization. Null amount means not commissionable. */
    public function setCommission(bool $isCommissionable, ?Money $amount): void
    {
        $this->isCommissionable = $isCommissionable;
        $this->commissionAmount = $isCommissionable ? $amount : null;
    }

    /**
     * Correcting a product's specs after creation (e.g. a storage size was mistyped at intake).
     * The caller (Handler) is responsible for INV-BR-08's "one SKU per exact configuration"
     * invariant — this method only holds the new value, it doesn't check for a collision.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function updateAttributes(array $attributes): void
    {
        $this->attributes = $attributes;
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

    public function isCommissionable(): bool
    {
        return $this->isCommissionable;
    }

    public function commissionAmount(): ?Money
    {
        return $this->commissionAmount;
    }

    /** @return array<string, mixed> */
    public function attributes(): array
    {
        return $this->attributes;
    }
}
