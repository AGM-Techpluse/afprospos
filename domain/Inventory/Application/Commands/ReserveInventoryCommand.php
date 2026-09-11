<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Commands;

/**
 * The single reservation contract used by both Repairs and Sales
 * (Implementation Plan Phase 3). `quantity` drives non-serialized
 * reservation; serialized SKUs reserve `quantity` distinct units
 * (usually 1) and the Handler picks which ones (BLD §2.3).
 */
final readonly class ReserveInventoryCommand
{
    public function __construct(
        public int $skuId,
        public int $shopId,
        public int $quantity,
        public string $sourceType,
        public int $sourceId,
    ) {}
}
