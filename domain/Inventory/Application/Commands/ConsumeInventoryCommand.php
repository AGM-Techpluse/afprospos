<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Commands;

/**
 * Converts an existing reservation into a permanent stock-out
 * (BLD §2.2). Same shape as ReserveInventoryCommand deliberately: for
 * serialized SKUs the Handler looks up the unit(s) already marked
 * `reserved_by_type`/`reserved_by_id` for this source rather than
 * requiring the caller to remember specific item ids; for
 * non-serialized SKUs there is no per-source ledger row (DBDD §14.4
 * only has an aggregate `reserved` counter), so the caller must supply
 * the quantity it originally reserved — same as any `sales_checkout_items`
 * row would.
 */
final readonly class ConsumeInventoryCommand
{
    public function __construct(
        public int $skuId,
        public int $shopId,
        public int $quantity,
        public string $sourceType,
        public int $sourceId,
    ) {}
}
