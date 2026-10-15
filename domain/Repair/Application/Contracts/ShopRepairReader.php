<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Contracts;

/** Read-side figures about a shop's repairs, for other modules' dashboards. */
interface ShopRepairReader
{
    /** @return int[] repair job ids for the shop — the payable ids of the Repairs revenue stream. */
    public function repairJobIds(int $shopId): array;
}
