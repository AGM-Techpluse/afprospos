<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Entities;

use Domain\Repair\Domain\ValueObjects\DeviceBrandId;
use Domain\Repair\Domain\ValueObjects\DeviceModelId;

/** A specific model offered under a DeviceBrand (e.g. "iPhone 15 Pro" under "Apple") — now carries its own fixable-fault/commission catalog (DeviceProblemTag.deviceModelId), not just a display/prefill value. */
final class DeviceModel
{
    private function __construct(
        private readonly ?DeviceModelId $id,
        private DeviceBrandId $deviceBrandId,
        private string $name,
        private int $sortOrder,
    ) {}

    public static function create(DeviceBrandId $deviceBrandId, string $name, int $sortOrder): self
    {
        return new self(null, $deviceBrandId, $name, $sortOrder);
    }

    public static function reconstitute(DeviceModelId $id, DeviceBrandId $deviceBrandId, string $name, int $sortOrder): self
    {
        return new self($id, $deviceBrandId, $name, $sortOrder);
    }

    public function rename(string $name): void
    {
        $this->name = $name;
    }

    public function reorder(int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }

    public function reassignBrand(DeviceBrandId $deviceBrandId): void
    {
        $this->deviceBrandId = $deviceBrandId;
    }

    public function id(): ?DeviceModelId
    {
        return $this->id;
    }

    public function deviceBrandId(): DeviceBrandId
    {
        return $this->deviceBrandId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function sortOrder(): int
    {
        return $this->sortOrder;
    }
}
