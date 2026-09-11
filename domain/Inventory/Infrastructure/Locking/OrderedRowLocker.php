<?php

declare(strict_types=1);

namespace Domain\Inventory\Infrastructure\Locking;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * ADD §30.1: "define and document a consistent [lock] order... never
 * dynamically lock inventory rows in arbitrary UI order." One
 * `WHERE id IN (...sorted) ORDER BY id FOR UPDATE` statement, reused by
 * every repository that needs to lock multiple inventory rows in the
 * same transaction, so the ordering rule lives in exactly one place
 * instead of being re-remembered per repository method.
 */
final class OrderedRowLocker
{
    /**
     * @param  int[]  $ids
     * @return Collection<int, Model>
     */
    public static function lock(Builder $query, array $ids): Collection
    {
        if ($ids === []) {
            return new Collection;
        }

        $sortedIds = $ids;
        sort($sortedIds);

        return $query->whereIn('id', $sortedIds)->orderBy('id')->lockForUpdate()->get();
    }
}
