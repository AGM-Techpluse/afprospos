<?php

declare(strict_types=1);

namespace Domain\RBAC\Application\Queries;

use Domain\RBAC\Infrastructure\Persistence\Eloquent\StaffShopGrantRecord;

final class StaffShopGrantsQuery
{
    /** @return int[] */
    public function activeShopIdsForStaff(int $staffId): array
    {
        return StaffShopGrantRecord::query()
            ->where('staff_id', $staffId)
            ->whereNull('revoked_at')
            ->pluck('shop_id')
            ->map(static fn (int|string $id): int => (int) $id)
            ->all();
    }
}
