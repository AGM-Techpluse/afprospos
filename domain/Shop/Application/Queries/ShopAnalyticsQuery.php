<?php

declare(strict_types=1);

namespace Domain\Shop\Application\Queries;

use Domain\Inventory\Application\Contracts\ShopStockReader;
use Domain\Payments\Application\Contracts\PaymentRevenueReader;
use Domain\RBAC\Application\Contracts\ShopStaffReader;
use Domain\Repair\Application\Contracts\ShopRepairReader;
use Domain\Sales\Application\Contracts\ShopSalesReader;
use Illuminate\Support\Carbon;

/**
 * Read-side rollup backing the Admin Shop detail page's stat cards and revenue
 * chart. Every figure comes from the owning module's published read contract
 * (CPNC §4) — this class never reads another module's tables directly, so a
 * module can change its schema without breaking the Shop dashboard.
 */
final class ShopAnalyticsQuery
{
    private const REVENUE_SERIES_DAYS = 14;

    /** Shared by the day-by-day series and the all-time breakdown, so both agree on what counts as a stream. */
    private const REVENUE_STREAMS = [
        ['stream' => 'sales_checkout', 'label' => 'Sales'],
        ['stream' => 'repair_job', 'label' => 'Repairs'],
    ];

    public function __construct(
        private readonly ShopStaffReader $staff,
        private readonly ShopStockReader $stock,
        private readonly ShopSalesReader $sales,
        private readonly ShopRepairReader $repairs,
        private readonly PaymentRevenueReader $revenue,
    ) {}

    /**
     * @return array{
     *     staff_count: int,
     *     stock_units: int,
     *     sales_count: int,
     *     revenue_minor: int,
     *     revenue_series: array<int, array{date: string, sales_minor: int, repair_minor: int}>,
     *     revenue_breakdown: array<int, array{stream: string, label: string, revenue_minor: int}>,
     * }
     */
    public function forShop(int $shopId): array
    {
        return [
            'staff_count' => $this->staff->staffCount($shopId),
            'stock_units' => $this->stock->stockUnits($shopId),
            'sales_count' => $this->sales->salesCount($shopId),
            'revenue_minor' => $this->sales->revenueMinor($shopId),
            'revenue_series' => $this->revenueSeries($shopId),
            'revenue_breakdown' => $this->revenueBreakdown($shopId),
        ];
    }

    /**
     * Revenue by stream, from Payments' confirmed-transaction ledger. Streams with
     * zero confirmed revenue are omitted rather than rendered as an empty slice.
     *
     * @return array<int, array{stream: string, label: string, revenue_minor: int}>
     */
    private function revenueBreakdown(int $shopId): array
    {
        $breakdown = [];

        foreach (self::REVENUE_STREAMS as $stream) {
            $revenueMinor = $this->revenue->confirmedRevenueMinor($stream['stream'], $this->payableIds($stream['stream'], $shopId));

            if ($revenueMinor > 0) {
                $breakdown[] = ['stream' => $stream['stream'], 'label' => $stream['label'], 'revenue_minor' => $revenueMinor];
            }
        }

        return $breakdown;
    }

    /**
     * Per-day split for the line chart, from the same ledger as revenueBreakdown().
     *
     * @return array<int, array{date: string, sales_minor: int, repair_minor: int}>
     */
    private function revenueSeries(int $shopId): array
    {
        $since = Carbon::now()->subDays(self::REVENUE_SERIES_DAYS - 1)->startOfDay();

        $dailyByStream = [];
        foreach (self::REVENUE_STREAMS as $stream) {
            $dailyByStream[$stream['stream']] = $this->revenue->dailyConfirmedRevenue(
                $stream['stream'],
                $this->payableIds($stream['stream'], $shopId),
                $since,
            );
        }

        $series = [];
        for ($i = 0; $i < self::REVENUE_SERIES_DAYS; $i++) {
            $date = $since->copy()->addDays($i)->toDateString();
            $series[] = [
                'date' => $date,
                'sales_minor' => (int) ($dailyByStream['sales_checkout'][$date] ?? 0),
                'repair_minor' => (int) ($dailyByStream['repair_job'][$date] ?? 0),
            ];
        }

        return $series;
    }

    /** @return int[] the shop's payable ids for a revenue stream */
    private function payableIds(string $stream, int $shopId): array
    {
        return match ($stream) {
            'sales_checkout' => $this->sales->checkoutIds($shopId),
            'repair_job' => $this->repairs->repairJobIds($shopId),
        };
    }
}
