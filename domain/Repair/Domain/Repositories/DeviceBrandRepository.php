<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Repositories;

use Domain\Repair\Domain\Entities\DeviceBrand;
use Domain\Repair\Domain\ValueObjects\DeviceBrandId;
use Domain\Repair\Domain\ValueObjects\DeviceTypeId;

interface DeviceBrandRepository
{
    public function get(DeviceBrandId $id): DeviceBrand;

    public function save(DeviceBrand $deviceBrand): DeviceBrandId;

    public function delete(DeviceBrandId $id): void;

    /** Backs bulk-import idempotency — a repeat import must reuse an existing brand by (type, name), not create a duplicate. */
    public function findByTypeAndName(DeviceTypeId $deviceTypeId, string $name): ?DeviceBrand;
}
