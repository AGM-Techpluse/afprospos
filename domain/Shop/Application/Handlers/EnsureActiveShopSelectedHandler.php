<?php

declare(strict_types=1);

namespace Domain\Shop\Application\Handlers;

use Domain\Shop\Application\Contracts\ActiveShopSessionStore;

/**
 * A staff member with exactly one granted shop has nothing to actually
 * choose — requiring them to click a switcher control that, for them,
 * would only ever offer one option is friction with no payoff. Auto-select
 * it the moment it's unambiguous, on whichever request notices it's still
 * unset, rather than only on login. The owner is excluded: they hold every
 * shop, so defaulting to one arbitrarily would be more confusing than the
 * existing "all shops" view, and they have a real switcher to pick with.
 *
 * Shared by ResolveShopContext and HandleInertiaRequests (ADD §26.2) so the
 * page content and the shop-switcher prop agree within the same request —
 * each middleware computes its own inputs (they run at different points in
 * the stack) but both resolve through this one rule.
 */
final class EnsureActiveShopSelectedHandler
{
    public function __construct(private readonly ActiveShopSessionStore $session) {}

    /** @param  int[]  $grantedShopIds */
    public function resolve(bool $isOwner, array $grantedShopIds, ?int $currentActiveShopId): ?int
    {
        if ($currentActiveShopId !== null) {
            return $currentActiveShopId;
        }

        if ($isOwner || count($grantedShopIds) !== 1) {
            return null;
        }

        $soleShopId = $grantedShopIds[0];
        $this->session->setActiveShopId($soleShopId);

        return $soleShopId;
    }
}
