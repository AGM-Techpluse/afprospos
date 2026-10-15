<?php

declare(strict_types=1);

namespace Domain\Reports\Application\Queries;

use Domain\Reports\Application\Queries\Concerns\ScopesReportQueries;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class StaffReportsQuery
{
    use ScopesReportQueries;

    public function card(Carbon $start, Carbon $end, ?int $shopId, ?array $restrictToShopIds): array
    {
        $summary = $this->summary($shopId, $restrictToShopIds);

        return [
            'href' => '/admin/reports/staff',
            'metrics' => [
                ['label' => 'Active staff', 'value' => $summary['active_staff']],
                ['label' => 'Roles', 'value' => $summary['role_count']],
                ['label' => 'Actions', 'value' => $this->activityCount($start, $end)],
            ],
            'preview' => $this->roleDistribution($shopId, $restrictToShopIds),
        ];
    }

    public function report(Carbon $start, Carbon $end, ?int $shopId, ?array $restrictToShopIds): array
    {
        return [
            'summary' => $this->summary($shopId, $restrictToShopIds),
            'role_distribution' => $this->roleDistribution($shopId, $restrictToShopIds),
            'cashiers' => $this->cashierPerformance($start, $end, $shopId, $restrictToShopIds),
            'technicians' => $this->technicianPerformance($start, $end, $shopId, $restrictToShopIds),
            'activity' => $this->activity($start, $end),
        ];
    }

    private function summary(?int $shopId, ?array $restrictToShopIds): array
    {
        $staffIds = $this->staffIds($shopId, $restrictToShopIds);
        $staff = DB::table('staff');

        if ($staffIds !== null) {
            $staff->whereIn('id', $staffIds);
        }

        $row = $staff->selectRaw('count(*) as total_staff')
            ->selectRaw("sum(case when status = 'active' then 1 else 0 end) as active_staff")
            ->selectRaw("sum(case when status != 'active' then 1 else 0 end) as inactive_staff")
            ->first();

        return [
            'total_staff' => (int) $row->total_staff,
            'active_staff' => (int) $row->active_staff,
            'inactive_staff' => (int) $row->inactive_staff,
            'role_count' => (int) DB::table('roles')->where('guard_name', 'staff')->count(),
        ];
    }

    private function roleDistribution(?int $shopId, ?array $restrictToShopIds): array
    {
        $staffIds = $this->staffIds($shopId, $restrictToShopIds);
        $query = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('roles.guard_name', 'staff')
            ->select('roles.name as label')
            ->selectRaw('count(*) as value')
            ->groupBy('roles.name')
            ->orderByDesc('value');

        if ($staffIds !== null) {
            $query->whereIn('model_has_roles.model_id', $staffIds);
        }

        return $query->get()
            ->map(fn ($row): array => ['label' => (string) $row->label, 'value' => (int) $row->value])
            ->all();
    }

    private function cashierPerformance(Carbon $start, Carbon $end, ?int $shopId, ?array $restrictToShopIds): array
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

    private function technicianPerformance(Carbon $start, Carbon $end, ?int $shopId, ?array $restrictToShopIds): array
    {
        return $this->scopeShop(DB::table('repair_jobs'), $shopId, $restrictToShopIds, 'repair_jobs.shop_id')
            ->leftJoin('staff', 'staff.id', '=', 'repair_jobs.technician_staff_id')
            ->whereBetween('repair_jobs.created_at', [$start, $end])
            ->whereNotNull('repair_jobs.technician_staff_id')
            ->selectRaw('repair_jobs.technician_staff_id as staff_id, staff.name as name, count(*) as assigned')
            ->selectRaw("sum(case when repair_jobs.repair_status = 'completed' then 1 else 0 end) as completed")
            ->groupBy('repair_jobs.technician_staff_id', 'staff.name')
            ->orderByDesc('assigned')
            ->limit(10)
            ->get()
            ->map(fn ($row): array => [
                'staff_id' => (int) $row->staff_id,
                'name' => $row->name ?? 'Unknown staff',
                'assigned' => (int) $row->assigned,
                'completed' => (int) $row->completed,
            ])
            ->all();
    }

    private function activity(Carbon $start, Carbon $end): array
    {
        return DB::table('audit_logs')
            ->whereBetween('created_at', [$start, $end])
            ->select('event_type as label')
            ->selectRaw('count(*) as value')
            ->groupBy('event_type')
            ->orderByDesc('value')
            ->limit(12)
            ->get()
            ->map(fn ($row): array => ['label' => (string) $row->label, 'value' => (int) $row->value])
            ->all();
    }

    private function activityCount(Carbon $start, Carbon $end): int
    {
        return (int) DB::table('audit_logs')->whereBetween('created_at', [$start, $end])->count();
    }

    private function staffIds(?int $shopId, ?array $restrictToShopIds): ?array
    {
        if ($shopId === null && $restrictToShopIds === null) {
            return null;
        }

        $shopIds = $shopId !== null ? [$shopId] : ($restrictToShopIds ?? []);

        if ($shopIds === []) {
            return [];
        }

        $granted = DB::table('staff_shops')
            ->whereIn('shop_id', $shopIds)
            ->whereNull('revoked_at')
            ->pluck('staff_id')
            ->all();

        $owners = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('roles.name', config('afprospos.owner_role_name'))
            ->pluck('model_has_roles.model_id')
            ->all();

        return array_values(array_unique([...$granted, ...$owners]));
    }
}
