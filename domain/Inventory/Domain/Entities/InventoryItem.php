<?php

declare(strict_types=1);

namespace Domain\Inventory\Domain\Entities;

use Domain\Inventory\Domain\Exceptions\ItemNotReservable;
use Domain\Inventory\Domain\Exceptions\ItemNotTransferable;
use Domain\Inventory\Domain\ValueObjects\InventoryItemId;
use Domain\Inventory\Domain\ValueObjects\SkuId;
use Domain\Shared\Domain\ValueObjects\ShopId;

/**
 * One physical, serialized unit (e.g. one IMEI-tracked phone). Owns the
 * reservation state machine directly on itself — DBDD §14.3 has no
 * separate reservation table, so status/reserved_by_* IS the reservation
 * state (DBDD §27.2's invariant is enforced here, not read back later).
 */
final class InventoryItem
{
    private function __construct(
        private readonly InventoryItemId $id,
        private readonly SkuId $skuId,
        private readonly string $imei,
        private ShopId $currentShopId,
        private readonly string $condition,
        private string $status,
        private ?string $reservedByType,
        private ?int $reservedById,
        private int $version,
    ) {}

    public static function reconstitute(
        InventoryItemId $id,
        SkuId $skuId,
        string $imei,
        ShopId $currentShopId,
        string $condition,
        string $status,
        ?string $reservedByType,
        ?int $reservedById,
        int $version,
    ): self {
        return new self($id, $skuId, $imei, $currentShopId, $condition, $status, $reservedByType, $reservedById, $version);
    }

    /**
     * DBDD §5.3/§25.2: verify status=available, correct shop, and no
     * existing reservation before atomically reserving — this is what
     * makes "an IMEI cannot be reserved twice" true.
     */
    public function reserve(ShopId $requestedShop, string $sourceType, int $sourceId): void
    {
        if ($this->status !== 'available' || ! $this->currentShopId->equals($requestedShop) || $this->reservedById !== null) {
            throw ItemNotReservable::forItem($this->id->value);
        }

        $this->status = 'reserved';
        $this->reservedByType = $sourceType;
        $this->reservedById = $sourceId;
        $this->version++;
    }

    public function release(): void
    {
        if ($this->status !== 'reserved') {
            throw ItemNotReservable::forItem($this->id->value);
        }

        $this->status = 'available';
        $this->reservedByType = null;
        $this->reservedById = null;
        $this->version++;
    }

    /** BLD §2.2: Reserved -> consume -> Sold/Installed. */
    public function consume(): void
    {
        if ($this->status !== 'reserved') {
            throw ItemNotReservable::forItem($this->id->value);
        }

        $this->status = 'sold';
        $this->version++;
    }

    /** INV-BR-14: a reserved item cannot be transferred. */
    public function beginTransfer(): void
    {
        if ($this->status !== 'available') {
            throw ItemNotTransferable::forItem($this->id->value);
        }

        $this->status = 'transferring';
        $this->version++;
    }

    public function completeTransfer(ShopId $destinationShop): void
    {
        if ($this->status !== 'transferring') {
            throw ItemNotTransferable::forItem($this->id->value);
        }

        $this->currentShopId = $destinationShop;
        $this->status = 'available';
        $this->version++;
    }

    public function cancelTransfer(): void
    {
        if ($this->status !== 'transferring') {
            throw ItemNotTransferable::forItem($this->id->value);
        }

        $this->status = 'available';
        $this->version++;
    }

    public function id(): InventoryItemId
    {
        return $this->id;
    }

    public function skuId(): SkuId
    {
        return $this->skuId;
    }

    public function imei(): string
    {
        return $this->imei;
    }

    public function currentShopId(): ShopId
    {
        return $this->currentShopId;
    }

    public function condition(): string
    {
        return $this->condition;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function reservedByType(): ?string
    {
        return $this->reservedByType;
    }

    public function reservedById(): ?int
    {
        return $this->reservedById;
    }

    public function version(): int
    {
        return $this->version;
    }

    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }
}
