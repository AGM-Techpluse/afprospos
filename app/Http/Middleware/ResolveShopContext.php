<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Domain\RBAC\Application\Queries\EffectivePermissionsQuery;
use Domain\RBAC\Application\Queries\StaffShopGrantsQuery;
use Domain\RBAC\Domain\Policies\ShopScopePolicy;
use Domain\Shared\Application\DTOs\ActorContext;
use Domain\Shared\Domain\Exceptions\ShopScopeViolation;
use Domain\Shared\Domain\ValueObjects\StaffId;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Domain\Shop\Application\Contracts\ActiveShopSessionStore;
use Domain\Shop\Application\Queries\AccessibleShopsQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Builds this request's ActorContext and binds it into the container
 * (ADD §34/§35). Every later Controller/Handler resolves ActorContext
 * from the container instead of re-deriving permissions/shop-scope
 * itself — one computation per request, one source of truth per request.
 *
 * Must run AFTER `auth:staff` + EnsureStaffIsActive, and BEFORE
 * EnsurePermission, in the route middleware stack (ADD §26.2).
 */
final class ResolveShopContext
{
    public function __construct(
        private readonly EffectivePermissionsQuery $permissions,
        private readonly StaffShopGrantsQuery $grants,
        private readonly AccessibleShopsQuery $accessibleShops,
        private readonly ShopScopePolicy $shopScope,
        private readonly ActiveShopSessionStore $activeShopSession,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        /** @var StaffRecord $staff */
        $staff = Auth::guard('staff')->user();

        $isOwner = $this->permissions->staffHasRole($staff->id, (string) config('afprospos.owner_role_name'));
        $grantedShopIds = $this->grants->activeShopIdsForStaff($staff->id);

        $shopIds = $isOwner
            ? array_column($this->accessibleShops->forStaff($staff->id, isOwner: true), 'id')
            : $grantedShopIds;

        $activeShopId = $this->activeShopSession->getActiveShopId();

        if ($activeShopId !== null) {
            try {
                // Re-validated on every request, not only at switch time —
                // a grant revoked mid-session must lose effect immediately.
                $this->shopScope->ensureCanAccessShop($isOwner, $grantedShopIds, $activeShopId);
            } catch (ShopScopeViolation) {
                $activeShopId = null;
            }
        }

        $context = new ActorContext(
            staffId: new StaffId($staff->id),
            effectivePermissions: $this->permissions->forStaff($staff->id),
            shopIds: $shopIds,
            activeShopId: $activeShopId,
            isOwner: $isOwner,
        );

        app()->instance(ActorContext::class, $context);

        return $next($request);
    }
}
