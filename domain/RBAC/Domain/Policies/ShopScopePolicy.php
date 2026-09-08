<?php

declare(strict_types=1);

namespace Domain\RBAC\Domain\Policies;

use Domain\Shared\Domain\Exceptions\ShopScopeViolation;

/**
 * The authoritative, mutation-gating check (ADD §35: "never trust a
 * client-provided shop_id alone; validate it against effective
 * permissions"). Deliberately operates on primitives rather than the
 * `ActorContext` DTO — that DTO lives in Shared's *Application* layer,
 * and a Domain/ class may only depend on Shared Kernel *Domain*
 * abstractions (CPNC §4.1 Purity Law), never another module's Application
 * layer. `ActorContext::canAccessShop()` performs the same one-line check
 * for cheap read-side/UI purposes; this Policy is the version every
 * mutating Handler must call before touching shop-scoped state.
 */
final class ShopScopePolicy
{
    /** @param int[] $grantedShopIds */
    public function ensureCanAccessShop(bool $isOwner, array $grantedShopIds, int $requestedShopId): void
    {
        if ($isOwner || in_array($requestedShopId, $grantedShopIds, true)) {
            return;
        }

        throw ShopScopeViolation::forShop($requestedShopId);
    }
}
