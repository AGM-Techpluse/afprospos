<?php

declare(strict_types=1);

namespace Domain\Shared\Domain\Contracts;

use Domain\Shared\Domain\ValueObjects\ShopId;

/**
 * Lets Sales and Repair ask what kind of shop they are acting in, without reading
 * the Shop module's data. A warehouse holds and transfers stock; it never takes a
 * sale or a repair intake.
 */
interface ShopClassification
{
    public function isWarehouse(ShopId $shopId): bool;
}
