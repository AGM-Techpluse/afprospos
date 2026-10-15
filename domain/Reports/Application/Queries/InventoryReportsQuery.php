<?php

declare(strict_types=1);

namespace Domain\Reports\Application\Queries;

use Domain\Reports\Application\Queries\Concerns\ScopesReportQueries;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class InventoryReportsQuery
{
    use ScopesReportQueries;

    public function card(?int $shopId, ?array $restrictToShopIds): array
    {
        $summary = $this->summary($shopId, $restrictToShopIds);

        return [
            'href' => '/admin/reports/inventory',
            'metrics' => [
                ['label' => 'On hand', 'value' => $summary['on_hand_units']],
                ['label' => 'Reserved', 'value' => $summary['reserved_units']],
                ['label' => 'Stock value', 'value_minor' => $summary['selling_value_minor']],
            ],
            'preview' => $this->stockByCategory($shopId, $restrictToShopIds),
        ];
    }

    public function report(Carbon $start, Carbon $end, ?int $shopId, ?array $restrictToShopIds): array
    {
        return [
            'summary' => $this->summary($shopId, $restrictToShopIds),
            'stock_by_category' => $this->stockByCategory($shopId, $restrictToShopIds),
            'low_stock' => $this->lowStock($shopId, $restrictToShopIds),
            'transfers' => $this->transfers($start, $end, $shopId, $restrictToShopIds),
            'serialized_status' => $this->serializedStatus($shopId, $restrictToShopIds),
        ];
    }

    private function summary(?int $shopId, ?array $restrictToShopIds): array
    {
        $stock = $this->scopeShop(DB::table('inventory_stock_levels'), $shopId, $restrictToShopIds)
            ->join('inventory_skus', 'inventory_skus.id', '=', 'inventory_stock_levels.sku_id')
            ->selectRaw('coalesce(sum(inventory_stock_levels.on_hand), 0) as on_hand')
            ->selectRaw('coalesce(sum(inventory_stock_levels.reserved), 0) as reserved')
            ->selectRaw('coalesce(sum(inventory_stock_levels.on_hand * inventory_skus.cost_price_minor), 0) as cost_value_minor')
            ->selectRaw('coalesce(sum(inventory_stock_levels.on_hand * inventory_skus.selling_price_minor), 0) as selling_value_minor')
            ->first();

        $serialized = $this->scopeShop(DB::table('inventory_items'), $shopId, $restrictToShopIds, 'current_shop_id')
            ->where('status', '!=', 'sold')
            ->count();

        return [
            'sku_count' => (int) DB::table('inventory_skus')->count(),
            'serialized_units' => (int) $serialized,
            'on_hand_units' => (int) $stock->on_hand + (int) $serialized,
            'reserved_units' => (int) $stock->reserved + $this->reservedSerialized($shopId, $restrictToShopIds),
            'available_units' => ((int) $stock->on_hand - (int) $stock->reserved) + $this->availableSerialized($shopId, $restrictToShopIds),
            'cost_value_minor' => (int) $stock->cost_value_minor,
            'selling_value_minor' => (int) $stock->selling_value_minor,
            'low_stock_count' => count($this->lowStock($shopId, $restrictToShopIds)),
        ];
    }

    private function stockByCategory(?int $shopId, ?array $restrictToShopIds): array
    {
        return $this->scopeShop(DB::table('inventory_stock_levels'), $shopId, $restrictToShopIds)
            ->join('inventory_skus', 'inventory_skus.id', '=', 'inventory_stock_levels.sku_id')
            ->join('inventory_products', 'inventory_products.id', '=', 'inventory_skus.product_id')
            ->select('inventory_products.category as label')
            ->selectRaw('sum(inventory_stock_levels.on_hand) as value')
            ->selectRaw('sum(inventory_stock_levels.on_hand * inventory_skus.selling_price_minor) as value_minor')
            ->groupBy('inventory_products.category')
            ->orderByDesc('value')
            ->get()
            ->map(fn ($row): array => ['label' => (string) $row->label, 'value' => (int) $row->value, 'value_minor' => (int) $row->value_minor])
            ->all();
    }

    private function lowStock(?int $shopId, ?array $restrictToShopIds): array
    {
        return $this->scopeShop(DB::table('inventory_stock_levels'), $shopId, $restrictToShopIds)
            ->join('inventory_skus', 'inventory_skus.id', '=', 'inventory_stock_levels.sku_id')
            ->join('inventory_products', 'inventory_products.id', '=', 'inventory_skus.product_id')
            ->whereNotNull('inventory_skus.low_stock_threshold')
            ->whereRaw('(inventory_stock_levels.on_hand - inventory_stock_levels.reserved) <= inventory_skus.low_stock_threshold')
            ->select(
                'inventory_products.brand',
                'inventory_products.model',
                'inventory_skus.sku_code',
                'inventory_stock_levels.shop_id',
                'inventory_stock_levels.on_hand',
                'inventory_stock_levels.reserved',
                'inventory_skus.low_stock_threshold',
            )
            ->selectRaw('(inventory_stock_levels.on_hand - inventory_stock_levels.reserved) as available')
            ->orderBy('available')
            ->limit(20)
            ->get()
            ->map(fn ($row): array => [
                'product' => "{$row->brand} {$row->model}",
                'sku_code' => $row->sku_code,
                'shop_id' => (int) $row->shop_id,
                'on_hand' => (int) $row->on_hand,
                'reserved' => (int) $row->reserved,
                'available' => (int) $row->available,
                'low_stock_threshold' => (int) $row->low_stock_threshold,
            ])
            ->all();
    }

    private function transfers(Carbon $start, Carbon $end, ?int $shopId, ?array $restrictToShopIds): array
    {
        $query = DB::table('inventory_transfers')
            ->whereBetween('created_at', [$start, $end]);

        if ($shopId !== null) {
            $query->where(fn ($inner) => $inner->where('from_shop_id', $shopId)->orWhere('to_shop_id', $shopId));
        } elseif ($restrictToShopIds !== null) {
            $query->where(fn ($inner) => $inner->whereIn('from_shop_id', $restrictToShopIds)->orWhereIn('to_shop_id', $restrictToShopIds));
        }

        return $query->select('status as label')
            ->selectRaw('count(*) as value')
            ->groupBy('status')
            ->orderByDesc('value')
            ->get()
            ->map(fn ($row): array => ['label' => (string) $row->label, 'value' => (int) $row->value])
            ->all();
    }

    private function serializedStatus(?int $shopId, ?array $restrictToShopIds): array
    {
        return $this->scopeShop(DB::table('inventory_items'), $shopId, $restrictToShopIds, 'current_shop_id')
            ->select('status as label')
            ->selectRaw('count(*) as value')
            ->groupBy('status')
            ->orderByDesc('value')
            ->get()
            ->map(fn ($row): array => ['label' => (string) $row->label, 'value' => (int) $row->value])
            ->all();
    }

    private function reservedSerialized(?int $shopId, ?array $restrictToShopIds): int
    {
        return (int) $this->scopeShop(DB::table('inventory_items'), $shopId, $restrictToShopIds, 'current_shop_id')
            ->where('status', 'reserved')
            ->count();
    }

    private function availableSerialized(?int $shopId, ?array $restrictToShopIds): int
    {
        return (int) $this->scopeShop(DB::table('inventory_items'), $shopId, $restrictToShopIds, 'current_shop_id')
            ->where('status', 'available')
            ->count();
    }
}
