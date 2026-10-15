<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Contracts;

/** Read-side figure for other modules' dashboards (e.g. Shop analytics) — how much stock a shop holds. */
interface ShopStockReader
{
    /** Non-serialized on-hand units plus serialized units that are available or reserved in the shop. */
    public function stockUnits(int $shopId): int;
}
