<?php

declare(strict_types=1);

namespace Domain\Reports\Application\Queries;

use Domain\Reports\Application\Queries\Concerns\ScopesReportQueries;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class SalesReportsQuery
{
    use ScopesReportQueries;

    public function card(Carbon $start, Carbon $end, ?int $shopId, ?array $restrictToShopIds): array
    {
        $summary = $this->summary($start, $end, $shopId, $restrictToShopIds);

        return [
            'href' => '/admin/reports/sales',
            'metrics' => [
                ['label' => 'Revenue', 'value_minor' => $summary['gross_sales_minor']],
                ['label' => 'Sales', 'value' => $summary['sales_count']],
                ['label' => 'Avg order', 'value_minor' => $summary['average_order_minor']],
            ],
            'preview' => $this->paymentMethods($start, $end, $shopId, $restrictToShopIds),
        ];
    }

    public function report(Carbon $start, Carbon $end, ?int $shopId, ?array $restrictToShopIds): array
    {
        return [
            'summary' => $this->summary($start, $end, $shopId, $restrictToShopIds),
            'trend' => $this->trend($start, $end, $shopId, $restrictToShopIds),
            'payment_methods' => $this->paymentMethods($start, $end, $shopId, $restrictToShopIds),
            'top_products' => $this->topProducts($start, $end, $shopId, $restrictToShopIds),
            'cashiers' => $this->cashiers($start, $end, $shopId, $restrictToShopIds),
        ];
    }

    private function summary(Carbon $start, Carbon $end, ?int $shopId, ?array $restrictToShopIds): array
    {
        $row = $this->scopeShop(DB::table('sales_sales'), $shopId, $restrictToShopIds)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('count(*) as sales_count')
            ->selectRaw('coalesce(sum(total_minor), 0) as gross_sales_minor')
            ->first();

        $salesCount = (int) $row->sales_count;
        $grossSalesMinor = (int) $row->gross_sales_minor;

        return [
            'sales_count' => $salesCount,
            'gross_sales_minor' => $grossSalesMinor,
            'average_order_minor' => $salesCount > 0 ? (int) round($grossSalesMinor / $salesCount) : 0,
            'items_sold' => $this->itemsSold($start, $end, $shopId, $restrictToShopIds),
        ];
    }

    private function trend(Carbon $start, Carbon $end, ?int $shopId, ?array $restrictToShopIds): array
    {
        $rows = $this->scopeShop(DB::table('sales_sales'), $shopId, $restrictToShopIds)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as day, count(*) as sales_count, coalesce(sum(total_minor), 0) as revenue_minor')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        return array_map(
            fn (string $day): array => [
                'date' => $day,
                'sales_count' => (int) ($rows[$day]->sales_count ?? 0),
                'revenue_minor' => (int) ($rows[$day]->revenue_minor ?? 0),
            ],
            $this->dateSeries($start, $end),
        );
    }

    private function paymentMethods(Carbon $start, Carbon $end, ?int $shopId, ?array $restrictToShopIds): array
    {
        return $this->scopeShop(DB::table('sales_sales'), $shopId, $restrictToShopIds)
            ->whereBetween('created_at', [$start, $end])
            ->select('payment_method as label')
            ->selectRaw('coalesce(sum(total_minor), 0) as value_minor')
            ->selectRaw('count(*) as count')
            ->groupBy('payment_method')
            ->orderByDesc('value_minor')
            ->get()
            ->map(fn ($row): array => ['label' => (string) $row->label, 'value_minor' => (int) $row->value_minor, 'count' => (int) $row->count])
            ->all();
    }

    private function topProducts(Carbon $start, Carbon $end, ?int $shopId, ?array $restrictToShopIds): array
    {
        return $this->scopeShop(DB::table('sales_sales'), $shopId, $restrictToShopIds, 'sales_sales.shop_id')
            ->join('sales_checkout_items', 'sales_checkout_items.sales_checkout_id', '=', 'sales_sales.sales_checkout_id')
            ->join('inventory_skus', 'inventory_skus.id', '=', 'sales_checkout_items.sku_id')
            ->join('inventory_products', 'inventory_products.id', '=', 'inventory_skus.product_id')
            ->whereBetween('sales_sales.created_at', [$start, $end])
            ->select('inventory_products.brand', 'inventory_products.model')
            ->selectRaw('sum(sales_checkout_items.quantity) as quantity')
            ->selectRaw('sum(sales_checkout_items.quantity * sales_checkout_items.unit_price_minor) as revenue_minor')
            ->groupBy('inventory_products.brand', 'inventory_products.model')
            ->orderByDesc('quantity')
            ->limit(10)
            ->get()
            ->map(fn ($row): array => ['product' => "{$row->brand} {$row->model}", 'quantity' => (int) $row->quantity, 'revenue_minor' => (int) $row->revenue_minor])
            ->all();
    }

    private function cashiers(Carbon $start, Carbon $end, ?int $shopId, ?array $restrictToShopIds): array
    {
        return $this->scopeShop(DB::table('sales_sales'), $shopId, $restrictToShopIds, 'sales_sales.shop_id')
            ->leftJoin('staff', 'staff.id', '=', 'sales_sales.cashier_staff_id')
            ->whereBetween('sales_sales.created_at', [$start, $end])
            ->selectRaw('sales_sales.cashier_staff_id as staff_id, staff.name as name, count(*) as sales_count')
            ->selectRaw('coalesce(sum(sales_sales.total_minor), 0) as revenue_minor')
            ->groupBy('sales_sales.cashier_staff_id', 'staff.name')
            ->orderByDesc('revenue_minor')
            ->limit(10)
            ->get()
            ->map(fn ($row): array => [
                'staff_id' => (int) $row->staff_id,
                'name' => $row->name ?? 'Unknown staff',
                'sales_count' => (int) $row->sales_count,
                'revenue_minor' => (int) $row->revenue_minor,
            ])
            ->all();
    }

    private function itemsSold(Carbon $start, Carbon $end, ?int $shopId, ?array $restrictToShopIds): int
    {
        return (int) $this->scopeShop(DB::table('sales_sales'), $shopId, $restrictToShopIds, 'sales_sales.shop_id')
            ->join('sales_checkout_items', 'sales_checkout_items.sales_checkout_id', '=', 'sales_sales.sales_checkout_id')
            ->whereBetween('sales_sales.created_at', [$start, $end])
            ->sum('sales_checkout_items.quantity');
    }
}
