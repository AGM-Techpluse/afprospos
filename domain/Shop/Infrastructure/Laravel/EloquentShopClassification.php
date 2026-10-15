<?php

declare(strict_types=1);

namespace Domain\Shop\Infrastructure\Laravel;

use Domain\Shared\Domain\Contracts\ShopClassification;
use Domain\Shared\Domain\ValueObjects\ShopId;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;

final class EloquentShopClassification implements ShopClassification
{
    public function isWarehouse(ShopId $shopId): bool
    {
        return (bool) ShopRecord::query()->whereKey($shopId->value)->value('is_warehouse');
    }
}
