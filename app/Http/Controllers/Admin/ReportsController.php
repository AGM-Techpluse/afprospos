<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Domain\Commission\Application\Queries\CommissionReportQuery;
use Domain\Inventory\Application\Queries\LowStockQuery;
use Domain\Repair\Application\Queries\RepairsListQuery;
use Domain\Reports\Application\Queries\InventoryReportsQuery;
use Domain\Reports\Application\Queries\RepairReportsQuery;
use Domain\Reports\Application\Queries\SalesReportsQuery;
use Domain\Reports\Application\Queries\StaffReportsQuery;
use Domain\Sales\Application\Queries\SalesHistoryQuery;
use Domain\Shared\Application\DTOs\ActorContext;
use Domain\Shop\Application\Queries\AccessibleShopsQuery;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

final class ReportsController
{
    public function __construct(
        private readonly RepairReportsQuery $repairs,
        private readonly SalesReportsQuery $sales,
        private readonly InventoryReportsQuery $inventory,
        private readonly StaffReportsQuery $staff,
        private readonly CommissionReportQuery $commission,
        private readonly RepairsListQuery $repairsList,
        private readonly SalesHistoryQuery $salesHistory,
        private readonly LowStockQuery $lowStock,
        private readonly AccessibleShopsQuery $shops,
    ) {}

    public function index(): Response
    {
        $actor = app(ActorContext::class);
        $filters = $this->filters();

        return Inertia::render('Admin/Reports/Index', [
            'cards' => [
                'repairs' => $this->repairs->card($filters['start'], $filters['end'], $filters['shop_id'], $filters['restrict_to_shop_ids']),
                'sales' => $this->sales->card($filters['start'], $filters['end'], $filters['shop_id'], $filters['restrict_to_shop_ids']),
                'inventory' => $this->inventory->card($filters['shop_id'], $filters['restrict_to_shop_ids']),
                'staff' => $this->staff->card($filters['start'], $filters['end'], $filters['shop_id'], $filters['restrict_to_shop_ids']),
                'commission' => $this->commission->card($filters['start'], $filters['end'], $filters['shop_id'], $filters['restrict_to_shop_ids']),
            ],
            'filters' => $filters['public'],
            'shops' => $this->shops->forStaff($actor->staffId->value, $actor->isOwner),
        ]);
    }

    public function repairs(): Response
    {
        $filters = $this->filters();
        $request = request();

        return Inertia::render('Admin/Reports/Repairs', [
            'report' => $this->repairs->report($filters['start'], $filters['end'], $filters['shop_id'], $filters['restrict_to_shop_ids']),
            'repair_list' => $this->repairsList->paginate(
                status: $request->string('status')->toString() ?: null,
                shopId: $filters['shop_id'],
                page: $request->integer('page', 1),
                restrictToShopIds: $filters['restrict_to_shop_ids'],
                fromDate: $filters['start'],
                toDate: $filters['end'],
            ),
            'filters' => $filters['public'],
            'shops' => $this->accessibleShops(),
        ]);
    }

    public function sales(): Response
    {
        $filters = $this->filters();
        $request = request();

        return Inertia::render('Admin/Reports/Sales', [
            'report' => $this->sales->report($filters['start'], $filters['end'], $filters['shop_id'], $filters['restrict_to_shop_ids']),
            'sales_list' => $this->salesHistory->paginate(
                search: null,
                shopId: $filters['shop_id'],
                page: $request->integer('page', 1),
                restrictToShopIds: $filters['restrict_to_shop_ids'],
                fromDate: $filters['start'],
                toDate: $filters['end'],
            ),
            'filters' => $filters['public'],
            'shops' => $this->accessibleShops(),
        ]);
    }

    public function inventory(): Response
    {
        $filters = $this->filters();
        $request = request();

        return Inertia::render('Admin/Reports/Inventory', [
            'report' => $this->inventory->report($filters['start'], $filters['end'], $filters['shop_id'], $filters['restrict_to_shop_ids']),
            'low_stock_list' => $this->lowStock->paginate(
                shopId: $filters['shop_id'],
                search: $request->string('search')->toString() ?: null,
                page: $request->integer('page', 1),
                restrictToShopIds: $filters['restrict_to_shop_ids'],
            ),
            'filters' => $filters['public'],
            'shops' => $this->accessibleShops(),
        ]);
    }

    public function staff(): Response
    {
        $filters = $this->filters();

        return Inertia::render('Admin/Reports/Staff', [
            'report' => $this->staff->report($filters['start'], $filters['end'], $filters['shop_id'], $filters['restrict_to_shop_ids']),
            'commission_by_staff' => $this->commission->totalsByStaff($filters['start'], $filters['end'], $filters['shop_id'], $filters['restrict_to_shop_ids']),
            'filters' => $filters['public'],
            'shops' => $this->accessibleShops(),
        ]);
    }

    /** @return array<string, mixed> */
    private function filters(): array
    {
        $actor = app(ActorContext::class);
        $request = request();

        $request->validate([
            'range' => ['nullable', 'string', 'in:today,7_days,30_days,this_month,last_month,custom'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'shop_id' => ['nullable', 'integer'],
            'page' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'string'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $range = $request->string('range')->toString() ?: '30_days';
        [$start, $end] = $this->dateRange($range);

        if ($range === 'custom') {
            $start = $request->filled('start_date') ? Carbon::parse($request->string('start_date')->toString())->startOfDay() : $start;
            $end = $request->filled('end_date') ? Carbon::parse($request->string('end_date')->toString())->endOfDay() : $end;
        }

        $shopId = $request->has('shop_id') ? ($request->integer('shop_id') ?: null) : $actor->activeShopId;
        abort_if($shopId !== null && ! $actor->canAccessShop($shopId), 403);

        return [
            'start' => $start,
            'end' => $end,
            'shop_id' => $shopId,
            'restrict_to_shop_ids' => $actor->isOwner ? null : $actor->shopIds,
            'public' => [
                'range' => $range,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'shop_id' => $shopId,
            ],
        ];
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function dateRange(string $range): array
    {
        return match ($range) {
            'today' => [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()],
            '7_days' => [Carbon::today()->subDays(6)->startOfDay(), Carbon::today()->endOfDay()],
            'this_month' => [Carbon::today()->startOfMonth(), Carbon::today()->endOfDay()],
            'last_month' => [Carbon::today()->subMonthNoOverflow()->startOfMonth(), Carbon::today()->subMonthNoOverflow()->endOfMonth()],
            default => [Carbon::today()->subDays(29)->startOfDay(), Carbon::today()->endOfDay()],
        };
    }

    /** @return array<int, array{id:int, name:string, sku_prefix_code:string, is_warehouse:bool}> */
    private function accessibleShops(): array
    {
        $actor = app(ActorContext::class);

        return $this->shops->forStaff($actor->staffId->value, $actor->isOwner);
    }
}
