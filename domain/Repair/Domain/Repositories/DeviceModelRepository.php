<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Repositories;

use Domain\Repair\Domain\Entities\DeviceModel;
use Domain\Repair\Domain\ValueObjects\DeviceBrandId;
use Domain\Repair\Domain\ValueObjects\DeviceModelId;

interface DeviceModelRepository
{
    public function get(DeviceModelId $id): DeviceModel;

    public function save(DeviceModel $deviceModel): DeviceModelId;

    public function delete(DeviceModelId $id): void;

    /** Backs the quick-add intake flow's idempotency — (device_brand_id, name) is unique, so a repeat quick-add must reuse the existing row rather than erroring. */
    public function findByBrandAndName(DeviceBrandId $deviceBrandId, string $name): ?DeviceModel;
}
