<?php

declare(strict_types=1);

namespace Domain\Inventory\Domain\Entities;

use Domain\Inventory\Domain\Exceptions\InsufficientAvailableStock;
use Domain\Inventory\Domain\Policies\ReservationPolicy;
use Domain\Inventory\Domain\Policies\TransferPolicy;
use Domain\Inventory\Domain\Services\StockAvailability;
use Domain\Inventory\Domain\ValueObjects\SkuId;
use Domain\Inventory\Domain\ValueObjects\StockLevelId;
use Domain\Shared\Domain\ValueObjects\ShopId;
use InvalidArgumentException;

/**
 * Non-serialized stock for one (sku, shop) pair (DBDD §14.4). Available
 * is always computed, never stored (BLD §2.1) — see StockAvailability.
 */
final class InventoryStockLevel
{
    private function __construct(
        private readonly StockLevelId $id,
        private readonly SkuId $skuId,
        private readonly ShopId $shopId,
        private int $onHand,
        private int $reserved,
        private int $version,
    ) {}

    public static function reconstitute(
        StockLevelId $id,
        SkuId $skuId,
        ShopId $shopId,
        int $onHand,
        int $reserved,
        int $version,
    ): self {
        return new self($id, $skuId, $shopId, $onHand, $reserved, $version);
    }

    public function availableQuantity(): int
    {
        return StockAvailability::available($this->onHand, $this->reserved);
    }

    /** DBDD §25.1: available = on_hand - reserved; if available >= quantity, reserved += quantity, else fail. */
    public function reserve(int $quantity): void
    {
        $this->assertPositive($quantity);

        if (! (new ReservationPolicy)->canReserve($this->onHand, $this->reserved, $quantity)) {
            throw InsufficientAvailableStock::forSku($this->skuId->value, $this->shopId->value, $quantity, $this->availableQuantity());
        }

        $this->reserved += $quantity;
        $this->version++;
    }

    /** BLD §2.2: Reserved -> release (cancel/expire) -> Available. */
    public function release(int $quantity): void
    {
        $this->assertPositive($quantity);

        if ($quantity > $this->reserved) {
            throw new InvalidArgumentException('Cannot release more than is currently reserved.');
        }

        $this->reserved -= $quantity;
        $this->version++;
    }

    /** INV/REP-BR-04: converting a reservation into a stock-out reduces On-hand and its matching Reserved together. */
    public function consume(int $quantity): void
    {
        $this->assertPositive($quantity);

        if ($quantity > $this->reserved || $quantity > $this->onHand) {
            throw new InvalidArgumentException('Cannot consume more than is currently reserved/on hand.');
        }

        $this->onHand -= $quantity;
        $this->reserved -= $quantity;
        $this->version++;
    }

    /**
     * INV-BR-14: only the available (non-reserved) portion of on_hand
     * may leave for another shop. Mirrors `reserve()`'s availability
     * check but reduces on_hand immediately — the stock is physically
     * gone from this shop while in transit (ADD §15/DBDD §14.5), not
     * merely held.
     */
    public function transferOut(int $quantity): void
    {
        $this->assertPositive($quantity);

        if (! (new TransferPolicy)->canTransferQuantity($this->onHand, $this->reserved, $quantity)) {
            throw InsufficientAvailableStock::forSku($this->skuId->value, $this->shopId->value, $quantity, $this->availableQuantity());
        }

        $this->onHand -= $quantity;
        $this->version++;
    }

    /** BRD INV-11: stock-in (receiving new stock). */
    public function receive(int $quantity): void
    {
        $this->assertPositive($quantity);

        $this->onHand += $quantity;
        $this->version++;
    }

    /**
     * BRD INV-12: manual stock adjustment (damage, loss, correction).
     * Positive or negative delta; on_hand must never go below reserved
     * (DBDD §27.1: 0 <= reserved <= on_hand).
     */
    public function adjust(int $delta): void
    {
        $newOnHand = $this->onHand + $delta;

        if ($newOnHand < 0) {
            throw new InvalidArgumentException('Adjustment would make on-hand negative.');
        }

        if ($newOnHand < $this->reserved) {
            throw new InvalidArgumentException('Adjustment would make on-hand fall below currently reserved quantity.');
        }

        $this->onHand = $newOnHand;
        $this->version++;
    }

    private function assertPositive(int $quantity): void
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Quantity must be a positive integer.');
        }
    }

    public function id(): StockLevelId
    {
        return $this->id;
    }

    public function skuId(): SkuId
    {
        return $this->skuId;
    }

    public function shopId(): ShopId
    {
        return $this->shopId;
    }

    public function onHand(): int
    {
        return $this->onHand;
    }

    public function reserved(): int
    {
        return $this->reserved;
    }

    public function version(): int
    {
        return $this->version;
    }
}
