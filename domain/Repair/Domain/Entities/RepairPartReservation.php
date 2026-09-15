<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Entities;

use Domain\Repair\Domain\Exceptions\PartReservationNotInstallable;
use Domain\Repair\Domain\ValueObjects\RepairJobId;
use Domain\Repair\Domain\ValueObjects\RepairPartReservationId;

/**
 * This reservation's own bookkeeping (has the part been physically
 * fitted yet?) — separate from Inventory's own item-level status.
 * Consumption happens at markInstalled(), not at reserve() (Phase 6
 * scope decision: reservation is never silent consumption).
 */
final class RepairPartReservation
{
    private function __construct(
        private readonly ?RepairPartReservationId $id,
        private readonly RepairJobId $repairJobId,
        private readonly ?int $inventoryItemId,
        private readonly int $skuId,
        private readonly int $quantity,
        private string $status,
    ) {}

    public static function reserve(RepairJobId $repairJobId, ?int $inventoryItemId, int $skuId, int $quantity): self
    {
        return new self(null, $repairJobId, $inventoryItemId, $skuId, $quantity, 'reserved');
    }

    public static function reconstitute(
        RepairPartReservationId $id,
        RepairJobId $repairJobId,
        ?int $inventoryItemId,
        int $skuId,
        int $quantity,
        string $status,
    ): self {
        return new self($id, $repairJobId, $inventoryItemId, $skuId, $quantity, $status);
    }

    public function markInstalled(): void
    {
        if ($this->status !== 'reserved') {
            throw PartReservationNotInstallable::forReservation($this->id?->value ?? 0, $this->status);
        }

        $this->status = 'installed';
    }

    public function markReleased(): void
    {
        if ($this->status !== 'reserved') {
            throw PartReservationNotInstallable::notReleasable($this->id?->value ?? 0, $this->status);
        }

        $this->status = 'released';
    }

    public function id(): ?RepairPartReservationId
    {
        return $this->id;
    }

    public function repairJobId(): RepairJobId
    {
        return $this->repairJobId;
    }

    public function inventoryItemId(): ?int
    {
        return $this->inventoryItemId;
    }

    public function skuId(): int
    {
        return $this->skuId;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function status(): string
    {
        return $this->status;
    }
}
