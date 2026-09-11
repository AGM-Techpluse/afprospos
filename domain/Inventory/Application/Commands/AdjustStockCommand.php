<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Commands;

/** BRD INV-12: manual stock adjustment, non-serialized only, with a mandatory reason. */
final readonly class AdjustStockCommand
{
    public function __construct(
        public int $skuId,
        public int $shopId,
        public int $delta,
        public string $reason,
        public int $adjustedByStaffId,
    ) {}
}
