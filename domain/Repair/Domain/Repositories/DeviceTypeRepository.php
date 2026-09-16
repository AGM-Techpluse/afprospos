<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Repositories;

use Domain\Repair\Domain\Entities\DeviceType;
use Domain\Repair\Domain\ValueObjects\DeviceTypeId;

interface DeviceTypeRepository
{
    public function get(DeviceTypeId $id): DeviceType;

    public function save(DeviceType $deviceType): DeviceTypeId;

    public function delete(DeviceTypeId $id): void;
}
