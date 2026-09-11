<?php

declare(strict_types=1);

namespace Domain\Sales\Domain\Entities;

use Domain\Sales\Domain\ValueObjects\SalesCheckoutItemId;
use Domain\Shared\Domain\ValueObjects\Money;

/**
 * One reserved line item on a checkout (DBDD §13.2). `unitPriceMinor`
 * is a price snapshot taken at add-time (DBDD §28) — it never changes
 * even if the SKU's catalog price changes later. `skuId`/`inventoryItemId`
 * are opaque cross-module IDs (CPNC §4.3) — Sales never depends on
 * Inventory's Domain types directly, only on plain ints it was handed
 * back by InventoryReservationService.
 */
final class SalesCheckoutItem
{
    private function __construct(
        private readonly ?SalesCheckoutItemId $id,
        private readonly int $skuId,
        private readonly ?int $inventoryItemId,
        private readonly int $quantity,
        private readonly Money $unitPrice,
    ) {}

    public static function create(int $skuId, ?int $inventoryItemId, int $quantity, Money $unitPrice): self
    {
        return new self(null, $skuId, $inventoryItemId, $quantity, $unitPrice);
    }

    public static function reconstitute(
        SalesCheckoutItemId $id,
        int $skuId,
        ?int $inventoryItemId,
        int $quantity,
        Money $unitPrice,
    ): self {
        return new self($id, $skuId, $inventoryItemId, $quantity, $unitPrice);
    }

    public function lineTotal(): Money
    {
        $total = Money::zero();
        for ($i = 0; $i < $this->quantity; $i++) {
            $total = $total->add($this->unitPrice);
        }

        return $total;
    }

    public function id(): ?SalesCheckoutItemId
    {
        return $this->id;
    }

    public function skuId(): int
    {
        return $this->skuId;
    }

    public function inventoryItemId(): ?int
    {
        return $this->inventoryItemId;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function unitPrice(): Money
    {
        return $this->unitPrice;
    }
}
