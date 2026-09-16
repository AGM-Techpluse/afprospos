<?php

declare(strict_types=1);

namespace Domain\Repair\Infrastructure\Persistence\Repositories;

use Domain\Repair\Domain\Repositories\DeviceProblemSuggestedPartRepository;
use Domain\Repair\Domain\ValueObjects\DeviceProblemTagId;
use Domain\Repair\Infrastructure\Persistence\Eloquent\DeviceProblemSuggestedSkuRecord;

final class EloquentDeviceProblemSuggestedPartRepository implements DeviceProblemSuggestedPartRepository
{
    public function attach(DeviceProblemTagId $problemTagId, int $skuId): int
    {
        $record = DeviceProblemSuggestedSkuRecord::query()->firstOrCreate([
            'device_problem_tag_id' => $problemTagId->value,
            'sku_id' => $skuId,
        ]);

        return $record->id;
    }

    public function detach(int $id): void
    {
        DeviceProblemSuggestedSkuRecord::query()->whereKey($id)->delete();
    }

    public function forProblemTag(DeviceProblemTagId $problemTagId): array
    {
        return DeviceProblemSuggestedSkuRecord::query()
            ->where('device_problem_tag_id', $problemTagId->value)
            ->get()
            ->map(static fn (DeviceProblemSuggestedSkuRecord $record): array => ['id' => $record->id, 'sku_id' => $record->sku_id])
            ->all();
    }
}
