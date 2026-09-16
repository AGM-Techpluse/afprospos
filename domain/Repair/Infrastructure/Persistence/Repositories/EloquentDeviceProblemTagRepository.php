<?php

declare(strict_types=1);

namespace Domain\Repair\Infrastructure\Persistence\Repositories;

use Domain\Repair\Domain\Entities\DeviceProblemTag;
use Domain\Repair\Domain\Repositories\DeviceProblemTagRepository;
use Domain\Repair\Domain\ValueObjects\DeviceProblemTagId;
use Domain\Repair\Domain\ValueObjects\DeviceTypeId;
use Domain\Repair\Infrastructure\Persistence\Eloquent\DeviceProblemTagRecord;

final class EloquentDeviceProblemTagRepository implements DeviceProblemTagRepository
{
    public function get(DeviceProblemTagId $id): DeviceProblemTag
    {
        return $this->toDomain(DeviceProblemTagRecord::query()->findOrFail($id->value));
    }

    public function save(DeviceProblemTag $tag): DeviceProblemTagId
    {
        $attributes = [
            'device_type_id' => $tag->deviceTypeId()->value,
            'label' => $tag->label(),
            'sort_order' => $tag->sortOrder(),
        ];

        if ($tag->id() === null) {
            $record = DeviceProblemTagRecord::query()->create($attributes);
        } else {
            $record = DeviceProblemTagRecord::query()->findOrFail($tag->id()->value);
            $record->update($attributes);
        }

        return new DeviceProblemTagId($record->id);
    }

    public function delete(DeviceProblemTagId $id): void
    {
        DeviceProblemTagRecord::query()->whereKey($id->value)->delete();
    }

    private function toDomain(DeviceProblemTagRecord $record): DeviceProblemTag
    {
        return DeviceProblemTag::reconstitute(
            new DeviceProblemTagId($record->id),
            new DeviceTypeId($record->device_type_id),
            $record->label,
            $record->sort_order,
        );
    }
}
