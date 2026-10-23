<?php

declare(strict_types=1);

namespace Domain\Repair\Infrastructure\Persistence\Repositories;

use Domain\Repair\Domain\Entities\DeviceProblemTag;
use Domain\Repair\Domain\Repositories\DeviceProblemTagRepository;
use Domain\Repair\Domain\ValueObjects\DeviceModelId;
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
            'device_model_id' => $tag->deviceModelId()?->value,
            'label' => $tag->label(),
            'sort_order' => $tag->sortOrder(),
            'commission_amount_minor' => $tag->commissionAmountMinor(),
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

    public function findByScopeAndLabel(DeviceTypeId $deviceTypeId, ?DeviceModelId $deviceModelId, string $label): ?DeviceProblemTag
    {
        $record = DeviceProblemTagRecord::query()
            ->where('device_type_id', $deviceTypeId->value)
            ->when(
                $deviceModelId !== null,
                fn ($query) => $query->where('device_model_id', $deviceModelId->value),
                fn ($query) => $query->whereNull('device_model_id'),
            )
            ->where('label', $label)
            ->first();

        return $record !== null ? $this->toDomain($record) : null;
    }

    private function toDomain(DeviceProblemTagRecord $record): DeviceProblemTag
    {
        return DeviceProblemTag::reconstitute(
            new DeviceProblemTagId($record->id),
            new DeviceTypeId($record->device_type_id),
            $record->device_model_id !== null ? new DeviceModelId($record->device_model_id) : null,
            $record->label,
            $record->sort_order,
            $record->commission_amount_minor,
        );
    }
}
