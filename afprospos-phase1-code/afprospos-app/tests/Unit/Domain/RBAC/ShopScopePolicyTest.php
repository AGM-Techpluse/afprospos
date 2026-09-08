<?php

declare(strict_types=1);

use Domain\RBAC\Domain\Policies\ShopScopePolicy;
use Domain\Shared\Domain\Exceptions\ShopScopeViolation;

it('allows an owner to access any shop', function (): void {
    (new ShopScopePolicy)->ensureCanAccessShop(isOwner: true, grantedShopIds: [], requestedShopId: 999);
})->throwsNoExceptions();

it('allows a non-owner to access a granted shop', function (): void {
    (new ShopScopePolicy)->ensureCanAccessShop(isOwner: false, grantedShopIds: [1, 2], requestedShopId: 2);
})->throwsNoExceptions();

it('blocks a non-owner from an ungranted shop', function (): void {
    (new ShopScopePolicy)->ensureCanAccessShop(isOwner: false, grantedShopIds: [1, 2], requestedShopId: 3);
})->throws(ShopScopeViolation::class);
