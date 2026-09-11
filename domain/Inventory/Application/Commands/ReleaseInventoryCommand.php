<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Commands;

/** Releases a reservation back to Available (cancel/expire — BLD §2.2). Same shape as ReserveInventoryCommand/ConsumeInventoryCommand. */
final readonly class ReleaseInventoryCommand
{
    public function __construct(
        public int $skuId,
        public int $shopId,
        public int $quantity,
        public string $sourceType,
        public int $sourceId,
    ) {}
}
