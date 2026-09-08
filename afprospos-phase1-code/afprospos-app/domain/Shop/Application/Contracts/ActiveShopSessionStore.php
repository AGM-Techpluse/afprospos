<?php

declare(strict_types=1);

namespace Domain\Shop\Application\Contracts;

/**
 * The "currently selected shop" is per-browser-session UI state, not a
 * business fact — it does not need a database column or an audit trail
 * of its own (switching shops is not itself an authorization change,
 * just a viewport; ResolveShopContext middleware re-validates the
 * stored ID against the actor's grants on every request regardless).
 */
interface ActiveShopSessionStore
{
    public function setActiveShopId(int $shopId): void;

    public function getActiveShopId(): ?int;
}
