<?php

declare(strict_types=1);

namespace Domain\Repair\Infrastructure\Persistence\Repositories;

use Domain\Repair\Domain\Entities\CommonComponent;
use Domain\Repair\Domain\Repositories\CommonComponentRepository;
use Domain\Repair\Domain\ValueObjects\CommonComponentId;
use Domain\Repair\Infrastructure\Persistence\Eloquent\CommonComponentRecord;

final class EloquentCommonComponentRepository implements CommonComponentRepository
{
    public function get(CommonComponentId $id): CommonComponent
    {
        return $this->toDomain(CommonComponentRecord::query()->findOrFail($id->value));
    }

    public function save(CommonComponent $component): CommonComponentId
    {
        $attributes = [
            'label' => $component->label(),
            'sort_order' => $component->sortOrder(),
            'commission_amount_minor' => $component->commissionAmountMinor(),
        ];

        if ($component->id() === null) {
            $record = CommonComponentRecord::query()->create($attributes);
        } else {
            $record = CommonComponentRecord::query()->findOrFail($component->id()->value);
            $record->update($attributes);
        }

        return new CommonComponentId($record->id);
    }

    public function delete(CommonComponentId $id): void
    {
        CommonComponentRecord::query()->whereKey($id->value)->delete();
    }

    private function toDomain(CommonComponentRecord $record): CommonComponent
    {
        return CommonComponent::reconstitute(
            new CommonComponentId($record->id),
            $record->label,
            $record->sort_order,
            $record->commission_amount_minor,
        );
    }
}
