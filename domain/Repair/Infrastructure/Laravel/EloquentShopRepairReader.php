<?php

declare(strict_types=1);

namespace Domain\Repair\Infrastructure\Laravel;

use Domain\Repair\Application\Contracts\ShopRepairReader;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;

final class EloquentShopRepairReader implements ShopRepairReader
{
    public function repairJobIds(int $shopId): array
    {
        return RepairJobRecord::query()->where('shop_id', $shopId)->pluck('id')->map(fn ($id): int => (int) $id)->all();
    }
}
