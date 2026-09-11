<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\DTOs;

/** What actually got reserved — the caller (Sales/Repair) needs this to know which physical units it now holds. */
final readonly class ReservationResult
{
    /** @param  int[]  $inventoryItemIds  empty for non-serialized reservations */
    public function __construct(
        public int $skuId,
        public int $shopId,
        public int $quantity,
        public array $inventoryItemIds,
    ) {}
}
