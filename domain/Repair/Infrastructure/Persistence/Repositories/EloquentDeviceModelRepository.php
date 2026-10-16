<?php

declare(strict_types=1);

namespace Domain\Repair\Infrastructure\Persistence\Repositories;

use Domain\Repair\Domain\Entities\DeviceModel;
use Domain\Repair\Domain\Repositories\DeviceModelRepository;
use Domain\Repair\Domain\ValueObjects\DeviceBrandId;
use Domain\Repair\Domain\ValueObjects\DeviceModelId;
use Domain\Repair\Infrastructure\Persistence\Eloquent\DeviceModelRecord;

final class EloquentDeviceModelRepository implements DeviceModelRepository
{
    public function get(DeviceModelId $id): DeviceModel
    {
        return $this->toDomain(DeviceModelRecord::query()->findOrFail($id->value));
    }

    public function save(DeviceModel $deviceModel): DeviceModelId
    {
        $attributes = [
            'device_brand_id' => $deviceModel->deviceBrandId()->value,
            'name' => $deviceModel->name(),
            'sort_order' => $deviceModel->sortOrder(),
        ];

        if ($deviceModel->id() === null) {
            $record = DeviceModelRecord::query()->create($attributes);
        } else {
            $record = DeviceModelRecord::query()->findOrFail($deviceModel->id()->value);
            $record->update($attributes);
        }

        return new DeviceModelId($record->id);
    }

    public function delete(DeviceModelId $id): void
    {
        DeviceModelRecord::query()->whereKey($id->value)->delete();
    }

    public function findByBrandAndName(DeviceBrandId $deviceBrandId, string $name): ?DeviceModel
    {
        $record = DeviceModelRecord::query()
            ->where('device_brand_id', $deviceBrandId->value)
            ->where('name', $name)
            ->first();

        return $record !== null ? $this->toDomain($record) : null;
    }

    private function toDomain(DeviceModelRecord $record): DeviceModel
    {
        return DeviceModel::reconstitute(
            new DeviceModelId($record->id),
            new DeviceBrandId($record->device_brand_id),
            $record->name,
            $record->sort_order,
        );
    }
}
