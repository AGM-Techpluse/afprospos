<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Commands;

final readonly class ReconcileStockCountsCommand
{
    /** @param  array<int, array{sku_id: int, shop_id: int, physical_count: int, reason_code: string, note: ?string}>  $counts */
    public function __construct(
        public array $counts,
        public int $reconciledByStaffId,
    ) {}
}
