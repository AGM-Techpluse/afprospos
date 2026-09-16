<?php

declare(strict_types=1);

namespace Domain\Sales\Application\Queries;

use Domain\Identity\Application\Contracts\CustomerDirectoryQuery;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SaleRecord;
use Illuminate\Support\Carbon;

/** Backs Admin Sales/Index — paginated, search/filter per the standing table-pages rule. */
final class SalesHistoryQuery
{
    public function __construct(private readonly CustomerDirectoryQuery $customers) {}

    /** @return array{data: array<int, array<string, mixed>>, current_page:int, last_page:int, per_page:int, total:int} */
    public function paginate(?string $search, ?int $shopId, int $page, int $perPage = 20): array
    {
        $query = SaleRecord::query();

        if ($shopId !== null) {
            $query->where('shop_id', $shopId);
        }

        if ($search !== null && $search !== '') {
            $query->where('invoice_number', 'like', "%{$search}%");
        }

        $paginator = $query->orderByDesc('created_at')->paginate($perPage, ['*'], 'page', $page);
        $sales = $paginator->getCollection();
        $customers = $this->customers->findMany($sales->pluck('customer_id')->filter()->unique()->all());

        return [
            'data' => $sales->map(static fn (SaleRecord $sale): array => [
                'id' => $sale->id,
                'shop_id' => $sale->shop_id,
                'customer_id' => $sale->customer_id,
                'customer_name' => $sale->customer_id !== null ? ($customers[$sale->customer_id]['name'] ?? null) : null,
                'total_minor' => $sale->total_minor,
                'payment_method' => $sale->payment_method,
                'invoice_number' => $sale->invoice_number,
                'created_at' => $sale->created_at->toIso8601String(),
            ])->all(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    /** Backs the "New sale" empty state (Admin Sales/Checkout with no active checkout) — so the page isn't a bare form. */
    public function recentForCashier(int $cashierStaffId, int $limit = 5): array
    {
        return SaleRecord::query()
            ->where('cashier_staff_id', $cashierStaffId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(static fn (SaleRecord $sale): array => [
                'id' => $sale->id,
                'total_minor' => $sale->total_minor,
                'payment_method' => $sale->payment_method,
                'invoice_number' => $sale->invoice_number,
                'created_at' => $sale->created_at->toIso8601String(),
            ])->all();
    }

    /** Backs the "New sale" stat card — this cashier's own completed-sale volume for the current calendar day. */
    public function todayForCashier(int $cashierStaffId): array
    {
        $today = SaleRecord::query()
            ->where('cashier_staff_id', $cashierStaffId)
            ->whereDate('created_at', Carbon::today())
            ->selectRaw('count(*) as sales_count, coalesce(sum(total_minor), 0) as total_minor')
            ->first();

        return [
            'sales_count' => (int) $today->sales_count,
            'total_minor' => (int) $today->total_minor,
        ];
    }

    /** Backs the Admin Dashboard's "Today's sales" stat card — shop-wide (or all-shops when null) rather than per-cashier. */
    public function todayForShop(?int $shopId): array
    {
        $query = SaleRecord::query()->whereDate('created_at', Carbon::today());

        if ($shopId !== null) {
            $query->where('shop_id', $shopId);
        }

        $today = $query->selectRaw('count(*) as sales_count, coalesce(sum(total_minor), 0) as total_minor')->first();

        return [
            'sales_count' => (int) $today->sales_count,
            'total_minor' => (int) $today->total_minor,
        ];
    }

    /** Backs the Admin Dashboard's "Recent sales" list. */
    public function recent(?int $shopId, int $limit = 5): array
    {
        $query = SaleRecord::query();

        if ($shopId !== null) {
            $query->where('shop_id', $shopId);
        }

        return $query->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(static fn (SaleRecord $sale): array => [
                'id' => $sale->id,
                'shop_id' => $sale->shop_id,
                'customer_id' => $sale->customer_id,
                'total_minor' => $sale->total_minor,
                'payment_method' => $sale->payment_method,
                'invoice_number' => $sale->invoice_number,
                'created_at' => $sale->created_at->toIso8601String(),
            ])->all();
    }
}
