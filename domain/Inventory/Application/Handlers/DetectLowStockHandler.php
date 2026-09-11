<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Handlers;

use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Inventory\Application\Commands\DetectLowStockCommand;
use Domain\Inventory\Application\Queries\LowStockQuery;

/**
 * REP/INV-BR-06 / BLD §3.4: a low-stock alert is a distinct event from
 * a job-blocking parts shortage. The Notifications module (Phase 8)
 * doesn't exist yet, so this records an audit fact per SKU/shop that a
 * future notification dispatcher can pick up — same deferral pattern
 * used elsewhere in this plan (e.g. checkout expiry release).
 *
 * @return array<int, array<string, mixed>> the low-stock rows found, for the console command to print
 */
final class DetectLowStockHandler
{
    public function __construct(
        private readonly LowStockQuery $lowStock,
        private readonly AuditWriter $audit,
    ) {}

    public function handle(DetectLowStockCommand $command): array
    {
        $items = $this->lowStock->forShop($command->shopId);

        foreach ($items as $item) {
            $this->audit->record(
                module: 'Inventory',
                eventType: 'LowStockDetected',
                actorStaffId: null,
                actorRoleSnapshot: null,
                subjectType: 'stock_level',
                subjectId: $item['stock_level_id'],
                beforeState: null,
                afterState: [
                    'sku_code' => $item['sku_code'],
                    'shop_id' => $item['shop_id'],
                    'available' => $item['available'],
                    'low_stock_threshold' => $item['low_stock_threshold'],
                ],
            );
        }

        return $items;
    }
}
