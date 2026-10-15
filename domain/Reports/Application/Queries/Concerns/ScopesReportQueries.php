<?php

declare(strict_types=1);

namespace Domain\Reports\Application\Queries\Concerns;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;

trait ScopesReportQueries
{
    protected function dateSeries(Carbon $start, Carbon $end): array
    {
        $days = [];
        $cursor = $start->copy()->startOfDay();
        $last = $end->copy()->startOfDay();

        while ($cursor <= $last) {
            $days[] = $cursor->toDateString();
            $cursor->addDay();
        }

        return $days;
    }

    protected function scopeShop(Builder $query, ?int $shopId, ?array $restrictToShopIds, string $column = 'shop_id'): Builder
    {
        if ($shopId !== null) {
            return $query->where($column, $shopId);
        }

        if ($restrictToShopIds !== null) {
            if ($restrictToShopIds === []) {
                return $query->whereRaw('1 = 0');
            }

            return $query->whereIn($column, $restrictToShopIds);
        }

        return $query;
    }
}
