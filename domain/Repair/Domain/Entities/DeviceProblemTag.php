<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Entities;

use Domain\Repair\Domain\ValueObjects\DeviceProblemTagId;
use Domain\Repair\Domain\ValueObjects\DeviceTypeId;

/** A fixable-problem category under a DeviceType (e.g. "Screen", "Battery", "Water damage") — shown after brand selection at intake, selectable multi-pick, backs the Parts page's suggested-SKU mapping. */
final class DeviceProblemTag
{
    private function __construct(
        private readonly ?DeviceProblemTagId $id,
        private readonly DeviceTypeId $deviceTypeId,
        private string $label,
        private int $sortOrder,
    ) {}

    public static function create(DeviceTypeId $deviceTypeId, string $label, int $sortOrder): self
    {
        return new self(null, $deviceTypeId, $label, $sortOrder);
    }

    public static function reconstitute(DeviceProblemTagId $id, DeviceTypeId $deviceTypeId, string $label, int $sortOrder): self
    {
        return new self($id, $deviceTypeId, $label, $sortOrder);
    }

    public function rename(string $label): void
    {
        $this->label = $label;
    }

    public function reorder(int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }

    public function id(): ?DeviceProblemTagId
    {
        return $this->id;
    }

    public function deviceTypeId(): DeviceTypeId
    {
        return $this->deviceTypeId;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function sortOrder(): int
    {
        return $this->sortOrder;
    }
}
