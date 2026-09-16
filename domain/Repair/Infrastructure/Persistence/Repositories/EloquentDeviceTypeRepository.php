<?php

declare(strict_types=1);

namespace Domain\Repair\Infrastructure\Persistence\Repositories;

use Domain\Repair\Domain\Entities\DeviceType;
use Domain\Repair\Domain\Repositories\DeviceTypeRepository;
use Domain\Repair\Domain\ValueObjects\DeviceTypeId;
use Domain\Repair\Infrastructure\Persistence\Eloquent\DeviceTypeRecord;

final class EloquentDeviceTypeRepository implements DeviceTypeRepository
{
    public function get(DeviceTypeId $id): DeviceType
    {
        return $this->toDomain(DeviceTypeRecord::query()->findOrFail($id->value));
    }

    public function save(DeviceType $deviceType): DeviceTypeId
    {
        $attributes = [
            'label' => $deviceType->label(),
            'icon' => $deviceType->icon(),
            'sort_order' => $deviceType->sortOrder(),
        ];

        if ($deviceType->id() === null) {
            $record = DeviceTypeRecord::query()->create($attributes);
        } else {
            $record = DeviceTypeRecord::query()->findOrFail($deviceType->id()->value);
            $record->update($attributes);
        }

        return new DeviceTypeId($record->id);
    }

    public function delete(DeviceTypeId $id): void
    {
        DeviceTypeRecord::query()->whereKey($id->value)->delete();
    }

    private function toDomain(DeviceTypeRecord $record): DeviceType
    {
        return DeviceType::reconstitute(new DeviceTypeId($record->id), $record->label, $record->icon, $record->sort_order);
    }
}
