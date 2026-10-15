<?php

declare(strict_types=1);

namespace Domain\Reports\Application\Queries;

use Domain\Reports\Application\Queries\Concerns\ScopesReportQueries;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class RepairReportsQuery
{
    use ScopesReportQueries;

    public function card(Carbon $start, Carbon $end, ?int $shopId, ?array $restrictToShopIds): array
    {
        $summary = $this->summary($start, $end, $shopId, $restrictToShopIds);

        return [
            'href' => '/admin/reports/repairs',
            'metrics' => [
                ['label' => 'Received', 'value' => $summary['received']],
                ['label' => 'Completed', 'value' => $summary['completed']],
                ['label' => 'Repair revenue', 'value_minor' => $summary['repair_revenue_minor']],
            ],
            'preview' => $this->statusBreakdown($start, $end, $shopId, $restrictToShopIds),
        ];
    }

    public function report(Carbon $start, Carbon $end, ?int $shopId, ?array $restrictToShopIds): array
    {
        return [
            'summary' => $this->summary($start, $end, $shopId, $restrictToShopIds),
            'status_breakdown' => $this->statusBreakdown($start, $end, $shopId, $restrictToShopIds),
            'trend' => $this->trend($start, $end, $shopId, $restrictToShopIds),
            'technicians' => $this->technicians($start, $end, $shopId, $restrictToShopIds),
            'collections' => $this->collections($start, $end, $shopId, $restrictToShopIds),
            'fault_origin' => $this->faultOrigin($start, $end, $shopId, $restrictToShopIds),
        ];
    }

    private function summary(Carbon $start, Carbon $end, ?int $shopId, ?array $restrictToShopIds): array
    {
        $base = $this->scopeShop(DB::table('repair_jobs'), $shopId, $restrictToShopIds)
            ->whereBetween('created_at', [$start, $end]);

        $statuses = (clone $base)
            ->selectRaw('count(*) as received')
            ->selectRaw("sum(case when repair_status = 'completed' then 1 else 0 end) as completed")
            ->selectRaw("sum(case when repair_status = 'failed_requires_resolution' then 1 else 0 end) as failed")
            ->selectRaw("sum(case when repair_status = 'unrepairable' then 1 else 0 end) as unrepairable")
            ->selectRaw("sum(case when repair_status = 'cancelled' then 1 else 0 end) as cancelled")
            ->selectRaw("sum(case when repair_status not in ('completed', 'unrepairable', 'expired_cancelled', 'cancelled') then 1 else 0 end) as open_repairs")
            ->selectRaw('coalesce(sum(failure_service_charge_minor), 0) as service_charge_minor')
            ->selectRaw('coalesce(sum(failure_refund_due_minor), 0) as refund_due_minor')
            ->first();

        $payments = $this->repairPayments($shopId, $restrictToShopIds)
            ->where('payments_transactions.status', 'confirmed')
            ->whereBetween('payments_transactions.created_at', [$start, $end])
            ->selectRaw('coalesce(sum(payments_transactions.amount_minor), 0) as collected_minor')
            ->selectRaw("coalesce(sum(case when payments_transactions.purpose = 'repair_diagnosis_fee' then payments_transactions.amount_minor else 0 end), 0) as diagnosis_fee_minor")
            ->selectRaw("coalesce(sum(case when payments_transactions.purpose = 'repair_down_payment' then payments_transactions.amount_minor else 0 end), 0) as down_payment_minor")
            ->selectRaw("coalesce(sum(case when payments_transactions.purpose = 'repair_final_payment' then payments_transactions.amount_minor else 0 end), 0) as final_payment_minor")
            ->first();

        $received = (int) $statuses->received;
        $completed = (int) $statuses->completed;

        return [
            'received' => $received,
            'completed' => $completed,
            'failed' => (int) $statuses->failed,
            'unrepairable' => (int) $statuses->unrepairable,
            'cancelled' => (int) $statuses->cancelled,
            'open_repairs' => (int) $statuses->open_repairs,
            'completion_rate' => $received > 0 ? round(($completed / $received) * 100, 1) : 0,
            'cash_collected_minor' => (int) $payments->collected_minor,
            'diagnosis_fee_minor' => (int) $payments->diagnosis_fee_minor,
            'down_payment_minor' => (int) $payments->down_payment_minor,
            'final_payment_minor' => (int) $payments->final_payment_minor,
            'repair_revenue_minor' => (int) $payments->final_payment_minor + (int) $statuses->service_charge_minor,
            'service_charge_minor' => (int) $statuses->service_charge_minor,
            'refund_due_minor' => (int) $statuses->refund_due_minor,
            'earned_minor' => (int) $payments->diagnosis_fee_minor + (int) $payments->final_payment_minor + (int) $statuses->service_charge_minor,
        ];
    }

    private function statusBreakdown(Carbon $start, Carbon $end, ?int $shopId, ?array $restrictToShopIds): array
    {
        return $this->scopeShop(DB::table('repair_jobs'), $shopId, $restrictToShopIds)
            ->whereBetween('created_at', [$start, $end])
            ->select('repair_status as label')
            ->selectRaw('count(*) as value')
            ->groupBy('repair_status')
            ->orderByDesc('value')
            ->get()
            ->map(fn ($row): array => ['label' => (string) $row->label, 'value' => (int) $row->value])
            ->all();
    }

    private function trend(Carbon $start, Carbon $end, ?int $shopId, ?array $restrictToShopIds): array
    {
        $rows = $this->scopeShop(DB::table('repair_jobs'), $shopId, $restrictToShopIds)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as day, count(*) as received')
            ->selectRaw("sum(case when repair_status = 'completed' then 1 else 0 end) as completed")
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        return array_map(
            fn (string $day): array => [
                'date' => $day,
                'received' => (int) ($rows[$day]->received ?? 0),
                'completed' => (int) ($rows[$day]->completed ?? 0),
            ],
            $this->dateSeries($start, $end),
        );
    }

    private function technicians(Carbon $start, Carbon $end, ?int $shopId, ?array $restrictToShopIds): array
    {
        return $this->scopeShop(DB::table('repair_jobs'), $shopId, $restrictToShopIds, 'repair_jobs.shop_id')
            ->leftJoin('staff', 'staff.id', '=', 'repair_jobs.technician_staff_id')
            ->whereBetween('repair_jobs.created_at', [$start, $end])
            ->whereNotNull('repair_jobs.technician_staff_id')
            ->selectRaw('repair_jobs.technician_staff_id as staff_id, staff.name as name, count(*) as assigned')
            ->selectRaw("sum(case when repair_jobs.repair_status = 'completed' then 1 else 0 end) as completed")
            ->selectRaw("sum(case when repair_jobs.repair_status = 'failed_requires_resolution' then 1 else 0 end) as failed")
            ->groupBy('repair_jobs.technician_staff_id', 'staff.name')
            ->orderByDesc('assigned')
            ->limit(10)
            ->get()
            ->map(fn ($row): array => [
                'staff_id' => (int) $row->staff_id,
                'name' => $row->name ?? 'Unknown staff',
                'assigned' => (int) $row->assigned,
                'completed' => (int) $row->completed,
                'failed' => (int) $row->failed,
            ])
            ->all();
    }

    private function collections(Carbon $start, Carbon $end, ?int $shopId, ?array $restrictToShopIds): array
    {
        return $this->scopeShop(DB::table('collection_cases'), $shopId, $restrictToShopIds)
            ->where('source_type', 'repair_job')
            ->whereBetween('created_at', [$start, $end])
            ->select('status as label')
            ->selectRaw('count(*) as value')
            ->groupBy('status')
            ->orderByDesc('value')
            ->get()
            ->map(fn ($row): array => ['label' => (string) $row->label, 'value' => (int) $row->value])
            ->all();
    }

    /** Three mutually-exclusive origins, derivable from which (if either) catalog FK a fixed fault carries — see CPNC's fault-provenance note on the 3 fault tables. */
    private function faultOrigin(Carbon $start, Carbon $end, ?int $shopId, ?array $restrictToShopIds): array
    {
        return $this->scopeShop(
            DB::table('repair_fixed_faults')
                ->join('repair_jobs', 'repair_jobs.id', '=', 'repair_fixed_faults.repair_job_id'),
            $shopId,
            $restrictToShopIds,
            'repair_jobs.shop_id',
        )
            ->whereBetween('repair_jobs.created_at', [$start, $end])
            ->selectRaw("
                case
                    when repair_fixed_faults.device_problem_tag_id is not null then 'Diagnosed'
                    when repair_fixed_faults.common_component_id is not null then 'Quick check'
                    else 'Custom'
                end as label
            ")
            ->selectRaw('count(*) as value')
            ->groupBy('label')
            ->orderByDesc('value')
            ->get()
            ->map(fn ($row): array => ['label' => (string) $row->label, 'value' => (int) $row->value])
            ->all();
    }

    private function repairPayments(?int $shopId, ?array $restrictToShopIds): Builder
    {
        return $this->scopeShop(
            DB::table('payments_transactions')
                ->join('repair_jobs', function ($join): void {
                    $join->on('repair_jobs.id', '=', 'payments_transactions.payable_id')
                        ->where('payments_transactions.payable_type', '=', 'repair_job');
                }),
            $shopId,
            $restrictToShopIds,
            'repair_jobs.shop_id',
        );
    }
}
