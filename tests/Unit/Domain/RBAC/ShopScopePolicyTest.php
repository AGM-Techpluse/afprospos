<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RBAC;

use Domain\RBAC\Domain\Policies\ShopScopePolicy;
use Domain\Shared\Domain\Exceptions\ShopScopeViolation;
use PHPUnit\Framework\TestCase;

class ShopScopePolicyTest extends TestCase
{
    public function test_allows_an_owner_to_access_any_shop(): void
    {
        (new ShopScopePolicy)->ensureCanAccessShop(isOwner: true, grantedShopIds: [], requestedShopId: 999);
        $this->assertTrue(true); // if it doesn't throw, it passes
    }

    public function test_allows_a_non_owner_to_access_a_granted_shop(): void
    {
        (new ShopScopePolicy)->ensureCanAccessShop(isOwner: false, grantedShopIds: [1, 2], requestedShopId: 2);
        $this->assertTrue(true);
    }

    public function test_blocks_a_non_owner_from_an_ungranted_shop(): void
    {
        $this->expectException(ShopScopeViolation::class);
        (new ShopScopePolicy)->ensureCanAccessShop(isOwner: false, grantedShopIds: [1, 2], requestedShopId: 3);
    }
}
