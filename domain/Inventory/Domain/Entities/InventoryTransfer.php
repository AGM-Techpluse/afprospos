<?php

declare(strict_types=1);

namespace Domain\Inventory\Domain\Entities;

use Domain\Inventory\Domain\ValueObjects\InventoryTransferId;
use Domain\Inventory\Domain\ValueObjects\SkuId;
use Domain\Shared\Domain\ValueObjects\ShopId;
use Domain\Shared\Domain\ValueObjects\StaffId;
use InvalidArgumentException;

/**
 * An inter-shop transfer (DBDD §14.5). For a serialized transfer,
 * inventoryItemId is set; for a bulk transfer, quantity is set.
 */
final class InventoryTransfer
{
    private function __construct(
        private readonly InventoryTransferId $id,
        private readonly SkuId $skuId,
        private readonly ?int $inventoryItemId,
        private readonly ?int $quantity,
        private readonly ShopId $fromShopId,
        private readonly ShopId $toShopId,
        private readonly StaffId $initiatedByStaffId,
        private ?StaffId $receivedByStaffId,
        private string $status,
    ) {}

    public static function reconstitute(
        InventoryTransferId $id,
        SkuId $skuId,
        ?int $inventoryItemId,
        ?int $quantity,
        ShopId $fromShopId,
        ShopId $toShopId,
        StaffId $initiatedByStaffId,
        ?StaffId $receivedByStaffId,
        string $status,
    ): self {
        return new self($id, $skuId, $inventoryItemId, $quantity, $fromShopId, $toShopId, $initiatedByStaffId, $receivedByStaffId, $status);
    }

    public function receive(StaffId $receivedBy): void
    {
        if ($this->status !== 'in_transit') {
            throw new InvalidArgumentException('Only an in-transit transfer can be received.');
        }

        $this->status = 'completed';
        $this->receivedByStaffId = $receivedBy;
    }

    public function cancel(): void
    {
        if ($this->status !== 'in_transit') {
            throw new InvalidArgumentException('Only an in-transit transfer can be cancelled.');
        }

        $this->status = 'cancelled';
    }

    public function id(): InventoryTransferId
    {
        return $this->id;
    }

    public function skuId(): SkuId
    {
        return $this->skuId;
    }

    public function inventoryItemId(): ?int
    {
        return $this->inventoryItemId;
    }

    public function quantity(): ?int
    {
        return $this->quantity;
    }

    public function fromShopId(): ShopId
    {
        return $this->fromShopId;
    }

    public function toShopId(): ShopId
    {
        return $this->toShopId;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function receivedByStaffId(): ?StaffId
    {
        return $this->receivedByStaffId;
    }
}
