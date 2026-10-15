<?php

declare(strict_types=1);

namespace Domain\RBAC\Application\Contracts;

/** Read-side figure for other modules' dashboards — how many staff can work in a shop. */
interface ShopStaffReader
{
    /** Staff with an active grant to the shop, plus the shop owners (who reach every shop). */
    public function staffCount(int $shopId): int;
}
