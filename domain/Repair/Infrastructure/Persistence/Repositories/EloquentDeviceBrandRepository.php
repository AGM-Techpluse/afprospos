<?php

declare(strict_types=1);

namespace Domain\Repair\Infrastructure\Persistence\Repositories;

use Domain\Repair\Domain\Entities\DeviceBrand;
use Domain\Repair\Domain\Repositories\DeviceBrandRepository;
use Domain\Repair\Domain\ValueObjects\DeviceBrandId;
use Domain\Repair\Domain\ValueObjects\DeviceTypeId;
use Domain\Repair\Infrastructure\Persistence\Eloquent\DeviceBrandRecord;

final class EloquentDeviceBrandRepository implements DeviceBrandRepository
{
    public function get(DeviceBrandId $id): DeviceBrand
    {
        return $this->toDomain(DeviceBrandRecord::query()->findOrFail($id->value));
    }

    public function save(DeviceBrand $deviceBrand): DeviceBrandId
    {
        $attributes = [
            'device_type_id' => $deviceBrand->deviceTypeId()->value,
            'name' => $deviceBrand->name(),
            'sort_order' => $deviceBrand->sortOrder(),
        ];

        if ($deviceBrand->id() === null) {
            $record = DeviceBrandRecord::query()->create($attributes);
        } else {
            $record = DeviceBrandRecord::query()->findOrFail($deviceBrand->id()->value);
            $record->update($attributes);
        }

        return new DeviceBrandId($record->id);
    }

    public function delete(DeviceBrandId $id): void
    {
        DeviceBrandRecord::query()->whereKey($id->value)->delete();
    }

    private function toDomain(DeviceBrandRecord $record): DeviceBrand
    {
        return DeviceBrand::reconstitute(
            new DeviceBrandId($record->id),
            new DeviceTypeId($record->device_type_id),
            $record->name,
            $record->sort_order,
        );
    }
}
