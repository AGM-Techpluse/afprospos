<?php

declare(strict_types=1);

namespace Domain\RBAC\Infrastructure\Laravel;

use Domain\RBAC\Application\Contracts\ShopStaffReader;
use Domain\RBAC\Infrastructure\Persistence\Eloquent\StaffShopGrantRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;

final class EloquentShopStaffReader implements ShopStaffReader
{
    public function staffCount(int $shopId): int
    {
        $grantedStaffIds = StaffShopGrantRecord::query()
            ->where('shop_id', $shopId)
            ->whereNull('revoked_at')
            ->pluck('staff_id');

        $ownerStaffIds = StaffRecord::role(config('afprospos.owner_role_name'))->pluck('id');

        return $grantedStaffIds->merge($ownerStaffIds)->unique()->count();
    }
}
