<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Repositories;

use Domain\Repair\Domain\Entities\DeviceBrand;
use Domain\Repair\Domain\ValueObjects\DeviceBrandId;

interface DeviceBrandRepository
{
    public function get(DeviceBrandId $id): DeviceBrand;

    public function save(DeviceBrand $deviceBrand): DeviceBrandId;

    public function delete(DeviceBrandId $id): void;
}
