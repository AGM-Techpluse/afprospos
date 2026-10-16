<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Entities;

use Domain\Repair\Domain\ValueObjects\DeviceModelId;
use Domain\Repair\Domain\ValueObjects\DeviceProblemTagId;
use Domain\Repair\Domain\ValueObjects\DeviceTypeId;

/**
 * A fixable-problem category (e.g. "Screen", "Battery", "Water damage") — shown
 * after brand/model selection at intake, selectable multi-pick, backs the
 * Parts page's suggested-SKU mapping. `deviceTypeId` is always set
 * (denormalized) even on a model-specific tag, so type-level fallback
 * queries never need to join through brand; `deviceModelId` null means
 * this is the type-level/fallback tag, set means a model-specific override.
 */
final class DeviceProblemTag
{
    private function __construct(
        private readonly ?DeviceProblemTagId $id,
        private readonly DeviceTypeId $deviceTypeId,
        private readonly ?DeviceModelId $deviceModelId,
        private string $label,
        private int $sortOrder,
        private ?int $commissionAmountMinor = null,
    ) {}

    public static function create(DeviceTypeId $deviceTypeId, ?DeviceModelId $deviceModelId, string $label, int $sortOrder, ?int $commissionAmountMinor = null): self
    {
        return new self(null, $deviceTypeId, $deviceModelId, $label, $sortOrder, $commissionAmountMinor);
    }

    public static function reconstitute(DeviceProblemTagId $id, DeviceTypeId $deviceTypeId, ?DeviceModelId $deviceModelId, string $label, int $sortOrder, ?int $commissionAmountMinor = null): self
    {
        return new self($id, $deviceTypeId, $deviceModelId, $label, $sortOrder, $commissionAmountMinor);
    }

    public function rename(string $label): void
    {
        $this->label = $label;
    }

    public function reorder(int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }

    /** The flat technician commission earned when a fault tied to this tag is ticked as actually fixed. Null means not commissionable. */
    public function setCommissionAmount(?int $amountMinor): void
    {
        $this->commissionAmountMinor = $amountMinor;
    }

    public function id(): ?DeviceProblemTagId
    {
        return $this->id;
    }

    public function deviceTypeId(): DeviceTypeId
    {
        return $this->deviceTypeId;
    }

    public function deviceModelId(): ?DeviceModelId
    {
        return $this->deviceModelId;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function sortOrder(): int
    {
        return $this->sortOrder;
    }

    public function commissionAmountMinor(): ?int
    {
        return $this->commissionAmountMinor;
    }
}
