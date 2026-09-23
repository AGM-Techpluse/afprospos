<?php

declare(strict_types=1);

namespace Domain\Shop\Application\Handlers;

use Domain\Shop\Application\Contracts\ActiveShopSessionStore;

/**
 * A non-owner staff member with no active shop yet is auto-placed into
 * their first granted shop rather than left "shop-agnostic" — every list
 * Query in this app treats a `null` active shop as "no shop filter, show
 * everything" (the Owner's intentional all-shops view), so leaving a
 * multi-shop staff member unset let them see every shop's repairs,
 * inventory, and payments before ever picking one from the switcher, not
 * just the shops they're actually granted. Auto-selecting is safe: it
 * only ever lands them on a shop they already have a grant for, and the
 * real switcher is still right there to change it. Runs on whichever
 * request notices it's still unset, rather than only on login.
 *
 * The owner is excluded: they hold every shop, so defaulting to one
 * arbitrarily would be more confusing than the existing "all shops" view,
 * and they have a real switcher to pick with.
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

        if ($isOwner || $grantedShopIds === []) {
            return null;
        }

        $firstShopId = $grantedShopIds[0];
        $this->session->setActiveShopId($firstShopId);

        return $firstShopId;
    }
}
