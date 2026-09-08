<?php

declare(strict_types=1);

namespace Domain\Shop\Application\Handlers;

use Domain\RBAC\Application\Queries\EffectivePermissionsQuery;
use Domain\RBAC\Application\Queries\StaffShopGrantsQuery;
use Domain\RBAC\Domain\Policies\ShopScopePolicy;
use Domain\Shop\Application\Commands\SwitchActiveShopCommand;
use Domain\Shop\Application\Contracts\ActiveShopSessionStore;

final class SwitchActiveShopHandler
{
    public function __construct(
        private readonly StaffShopGrantsQuery $grants,
        private readonly EffectivePermissionsQuery $permissions,
        private readonly ShopScopePolicy $shopScope,
        private readonly ActiveShopSessionStore $session,
    ) {}

    public function handle(SwitchActiveShopCommand $command): void
    {
        $isOwner = $this->permissions->staffHasRole($command->staffId, config('afprospos.owner_role_name'));
        $grantedShopIds = $this->grants->activeShopIdsForStaff($command->staffId);

        // Throws ShopScopeViolation (-> HTTP 403) if not authorised.
        $this->shopScope->ensureCanAccessShop($isOwner, $grantedShopIds, $command->requestedShopId);

        $this->session->setActiveShopId($command->requestedShopId);
    }
}
