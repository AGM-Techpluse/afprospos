<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Handlers;

use Domain\Inventory\Application\Commands\ReconcileInventoryInvariantsCommand;
use Domain\Inventory\Application\DTOs\InvariantViolation;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryItemRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryStockLevelRecord;

/**
 * DBDD §27's reconciliation invariants, validated as a real check
 * (not a dashboard) per the Implementation Plan's "reconciliation
 * reporting" item.
 */
final class ReconcileInventoryInvariantsHandler
{
    /** @return InvariantViolation[] */
    public function handle(ReconcileInventoryInvariantsCommand $command): array
    {
        return [
            ...$this->checkStockLevels(),
            ...$this->checkSerializedItems(),
        ];
    }

    /** §27.1: 0 <= reserved <= on_hand. @return InvariantViolation[] */
    private function checkStockLevels(): array
    {
        return InventoryStockLevelRecord::query()
            ->where('reserved', '<', 0)
            ->orWhereColumn('reserved', '>', 'on_hand')
            ->get()
            ->map(static fn (InventoryStockLevelRecord $level): InvariantViolation => new InvariantViolation(
                'stock_level',
                $level->id,
                "reserved ({$level->reserved}) is outside [0, on_hand ({$level->on_hand})] for sku_id={$level->sku_id} shop_id={$level->shop_id}",
            ))
            ->all();
    }

    /**
     * §27.2: status=reserved <=> reserved_by_type/id are both set.
     *
     * @return InvariantViolation[]
     */
    private function checkSerializedItems(): array
    {
        $inconsistent = InventoryItemRecord::query()
            ->where(function ($query): void {
                $query->where('status', 'reserved')
                    ->where(function ($inner): void {
                        $inner->whereNull('reserved_by_type')->orWhereNull('reserved_by_id');
                    });
            })
            ->orWhere(function ($query): void {
                $query->where('status', '!=', 'reserved')
                    ->where(function ($inner): void {
                        $inner->whereNotNull('reserved_by_type')->orWhereNotNull('reserved_by_id');
                    });
            })
            ->get();

        return $inconsistent
            ->map(static fn (InventoryItemRecord $item): InvariantViolation => new InvariantViolation(
                'inventory_item',
                $item->id,
                "status={$item->status} but reserved_by_type=".($item->reserved_by_type ?? 'null').' reserved_by_id='.($item->reserved_by_id ?? 'null'),
            ))
            ->all();
    }
}
