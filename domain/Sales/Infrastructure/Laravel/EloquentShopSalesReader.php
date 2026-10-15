<?php

declare(strict_types=1);

namespace Domain\Sales\Infrastructure\Laravel;

use Domain\Sales\Application\Contracts\ShopSalesReader;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SaleRecord;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SalesCheckoutRecord;

final class EloquentShopSalesReader implements ShopSalesReader
{
    public function salesCount(int $shopId): int
    {
        return SaleRecord::query()->where('shop_id', $shopId)->count();
    }

    public function revenueMinor(int $shopId): int
    {
        return (int) SaleRecord::query()->where('shop_id', $shopId)->sum('total_minor');
    }

    public function checkoutIds(int $shopId): array
    {
        return SalesCheckoutRecord::query()->where('shop_id', $shopId)->pluck('id')->map(fn ($id): int => (int) $id)->all();
    }
}
