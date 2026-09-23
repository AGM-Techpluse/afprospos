<?php

namespace Tests;

use Domain\RBAC\Infrastructure\Persistence\Eloquent\StaffShopGrantRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * BRD RBAC-05: a non-owner staff member has no shop access until
     * explicitly granted one — most Feature tests need this for the staff
     * member under test to act on that shop's resources at all. Takes a
     * raw shop id (not a ShopRecord instance) since this repo has two
     * distinct Eloquent models for "shop" in different namespaces
     * (Domain\Shop and Domain\Shared) and callers use either one.
     */
    protected function grantShopAccess(StaffRecord $staff, int $shopId): void
    {
        StaffShopGrantRecord::query()->create([
            'staff_id' => $staff->id,
            'shop_id' => $shopId,
            'granted_by_staff_id' => null,
            'granted_at' => now(),
        ]);
    }
}
