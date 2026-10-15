<?php

declare(strict_types=1);

namespace Domain\Sales\Application\Contracts;

/** Read-side figures about a shop's sales, for other modules' dashboards. */
interface ShopSalesReader
{
    public function salesCount(int $shopId): int;

    public function revenueMinor(int $shopId): int;

    /** @return int[] checkout ids for the shop — the payable ids of the Sales revenue stream. */
    public function checkoutIds(int $shopId): array;
}
