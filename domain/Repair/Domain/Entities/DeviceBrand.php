<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Entities;

use Domain\Repair\Domain\ValueObjects\DeviceBrandId;
use Domain\Repair\Domain\ValueObjects\DeviceTypeId;

/** A brand offered under a DeviceType (e.g. "Apple" under "Smartphones") — display/prefill only, `RepairJob.device_make` stays free text. */
final class DeviceBrand
{
    private function __construct(
        private readonly ?DeviceBrandId $id,
        private readonly DeviceTypeId $deviceTypeId,
        private string $name,
        private int $sortOrder,
    ) {}

    public static function create(DeviceTypeId $deviceTypeId, string $name, int $sortOrder): self
    {
        return new self(null, $deviceTypeId, $name, $sortOrder);
    }

    public static function reconstitute(DeviceBrandId $id, DeviceTypeId $deviceTypeId, string $name, int $sortOrder): self
    {
        return new self($id, $deviceTypeId, $name, $sortOrder);
    }

    public function rename(string $name): void
    {
        $this->name = $name;
    }

    public function reorder(int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }

    public function id(): ?DeviceBrandId
    {
        return $this->id;
    }

    public function deviceTypeId(): DeviceTypeId
    {
        return $this->deviceTypeId;
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
