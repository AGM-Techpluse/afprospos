<?php

declare(strict_types=1);

namespace Domain\Shop\Application\Queries;

use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryItemRecord;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryStockLevelRecord;
use Domain\Payments\Infrastructure\Persistence\Eloquent\PaymentTransactionRecord;
use Domain\RBAC\Infrastructure\Persistence\Eloquent\StaffShopGrantRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SaleRecord;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SalesCheckoutRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Support\Carbon;

/**
 * Read-side rollup backing the Admin Shop detail page's stat cards and
 * revenue chart. Deliberately lives in Shop's own Query layer rather
 * than a dedicated Reporting module — Eloquent is permitted here
 * (Infrastructure query layer, CPNC §2.1), and this is a single-page
 * dashboard read, not a cross-module write, so the usual "Commands go
 * through Contracts" boundary doesn't apply (mirrors how
 * ProductController already reads ShopDirectoryQuery directly).
 * A dedicated Reporting module (Phase 9) is the right place for
 * anything beyond one shop's own detail page.
 */
final class ShopAnalyticsQuery
{
    private const REVENUE_SERIES_DAYS = 14;

    /**
     * @return array{
     *     staff_count: int,
     *     stock_units: int,
     *     sales_count: int,
     *     revenue_minor: int,
     *     revenue_series: array<int, array{date: string, revenue_minor: int}>,
     *     revenue_breakdown: array<int, array{stream: string, label: string, revenue_minor: int}>,
     * }
     */
    public function forShop(int $shopId): array
    {
        return [
            'staff_count' => $this->staffCount($shopId),
            'stock_units' => $this->stockUnits($shopId),
            'sales_count' => SaleRecord::query()->where('shop_id', $shopId)->count(),
            'revenue_minor' => (int) SaleRecord::query()->where('shop_id', $shopId)->sum('total_minor'),
            'revenue_series' => $this->revenueSeries($shopId),
            'revenue_breakdown' => $this->revenueBreakdown($shopId),
        ];
    }

    private function staffCount(int $shopId): int
    {
        $grantedStaffIds = StaffShopGrantRecord::query()
            ->where('shop_id', $shopId)
            ->whereNull('revoked_at')
            ->pluck('staff_id');

        $ownerStaffIds = StaffRecord::role(config('afprospos.owner_role_name'))->pluck('id');

        return $grantedStaffIds->merge($ownerStaffIds)->unique()->count();
    }

    /** Non-serialized on-hand units plus available serialized units — a simple "how much stock sits here" figure, not Available-for-sale math. */
    private function stockUnits(int $shopId): int
    {
        $nonSerialized = (int) InventoryStockLevelRecord::query()->where('shop_id', $shopId)->sum('on_hand');

        $serialized = InventoryItemRecord::query()
            ->where('current_shop_id', $shopId)
            ->whereIn('status', ['available', 'reserved'])
            ->count();

        return $nonSerialized + $serialized;
    }

    /**
     * Revenue by stream, for the Dashboard's pie chart — sourced from
     * Payments' confirmed transaction ledger (grouped by its own
     * `payable_type`, DBDD §16.1) rather than re-deriving "what counts as
     * revenue" separately per module, so a future stream (warranty,
     * trade-in) only ever needs one more entry in $streams, not a new
     * query shape. Streams with zero confirmed revenue are omitted rather
     * than rendered as an empty pie slice.
     *
     * @return array<int, array{stream: string, label: string, revenue_minor: int}>
     */
    private function revenueBreakdown(int $shopId): array
    {
        $streams = [
            ['stream' => 'sales_checkout', 'label' => 'Sales', 'payableTable' => 'sales_checkouts'],
            ['stream' => 'repair_job', 'label' => 'Repairs', 'payableTable' => 'repair_jobs'],
        ];

        $breakdown = [];

        foreach ($streams as $stream) {
            $payableIds = match ($stream['payableTable']) {
                'sales_checkouts' => SalesCheckoutRecord::query()->where('shop_id', $shopId)->pluck('id'),
                'repair_jobs' => RepairJobRecord::query()->where('shop_id', $shopId)->pluck('id'),
            };

            $revenueMinor = (int) PaymentTransactionRecord::query()
                ->where('payable_type', $stream['stream'])
                ->where('status', 'confirmed')
                ->whereIn('payable_id', $payableIds)
                ->sum('amount_minor');

            if ($revenueMinor > 0) {
                $breakdown[] = ['stream' => $stream['stream'], 'label' => $stream['label'], 'revenue_minor' => $revenueMinor];
            }
        }

        return $breakdown;
    }

    /** @return array<int, array{date: string, revenue_minor: int}> */
    private function revenueSeries(int $shopId): array
    {
        $since = Carbon::now()->subDays(self::REVENUE_SERIES_DAYS - 1)->startOfDay();

        $rows = SaleRecord::query()
            ->where('shop_id', $shopId)
            ->where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as day, SUM(total_minor) as revenue_minor')
            ->groupBy('day')
            ->pluck('revenue_minor', 'day');

        $series = [];
        for ($i = 0; $i < self::REVENUE_SERIES_DAYS; $i++) {
            $date = $since->copy()->addDays($i)->toDateString();
            $series[] = ['date' => $date, 'revenue_minor' => (int) ($rows[$date] ?? 0)];
        }

        return $series;
    }
}
