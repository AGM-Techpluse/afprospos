<?php

declare(strict_types=1);

namespace Domain\Commission\Application\Queries;

use Domain\Commission\Infrastructure\Persistence\Eloquent\CommissionLedgerEntryRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/** Backs Admin Commission/Index — paginated, filterable per the standing table-pages rule. */
final class CommissionReportQuery
{
    /**
     * Backs the Reports hub's "Commission" card (same {href, metrics, preview}
     * shape every other Reports module's card() returns).
     *
     * @param  int[]|null  $restrictToShopIds
     */
    public function card(Carbon $start, Carbon $end, ?int $shopId, ?array $restrictToShopIds): array
    {
        $byStaff = $this->totalsByStaff($start, $end, $shopId, $restrictToShopIds, 5);

        $query = $this->scopedEntries($start, $end, $shopId, $restrictToShopIds);
        $totalMinor = (int) (clone $query)->sum('commission_ledger_entries.commissionable_amount_minor');
        $entriesCount = (clone $query)->count();
        $staffCount = (clone $query)->distinct()->count('commission_ledger_entries.staff_id');

        return [
            'href' => '/admin/commission',
            'metrics' => [
                ['label' => 'Total commission', 'value_minor' => $totalMinor],
                ['label' => 'Entries', 'value' => $entriesCount],
                ['label' => 'Staff earning', 'value' => $staffCount],
            ],
            'preview' => array_map(
                static fn (array $row): array => ['label' => $row['name'], 'value_minor' => $row['total_minor']],
                $byStaff,
            ),
        ];
    }

    /**
     * Backs the Staff report's "Commission earned" table — a shop's staff
     * count is inherently small, so a generous cap (not real pagination,
     * matching how the Staff report's other per-staff lists already work)
     * is enough; the "View in Commission" link covers drilling into the
     * full, genuinely paginated entry log.
     *
     * @param  int[]|null  $restrictToShopIds
     * @return array<int, array{staff_id:int, name:string, role_snapshot:string, entries_count:int, total_minor:int}>
     */
    public function totalsByStaff(Carbon $start, Carbon $end, ?int $shopId, ?array $restrictToShopIds, int $limit = 100): array
    {
        return $this->scopedEntries($start, $end, $shopId, $restrictToShopIds)
            ->leftJoin('staff', 'staff.id', '=', 'commission_ledger_entries.staff_id')
            ->selectRaw('commission_ledger_entries.staff_id as staff_id, staff.name as name')
            ->selectRaw('max(commission_ledger_entries.role_snapshot) as role_snapshot')
            ->selectRaw('count(*) as entries_count')
            ->selectRaw('coalesce(sum(commission_ledger_entries.commissionable_amount_minor), 0) as total_minor')
            ->groupBy('commission_ledger_entries.staff_id', 'staff.name')
            ->orderByDesc('total_minor')
            ->limit($limit)
            ->get()
            ->map(fn ($row): array => [
                'staff_id' => (int) $row->staff_id,
                'name' => $row->name ?? 'Unknown staff',
                'role_snapshot' => (string) $row->role_snapshot,
                'entries_count' => (int) $row->entries_count,
                'total_minor' => (int) $row->total_minor,
            ])
            ->all();
    }

    /** @param  int[]|null  $restrictToShopIds */
    private function scopedEntries(Carbon $start, Carbon $end, ?int $shopId, ?array $restrictToShopIds): Builder
    {
        $query = DB::table('commission_ledger_entries')
            ->whereBetween('commission_ledger_entries.created_at', [$start, $end]);

        if ($shopId !== null) {
            $query->where('commission_ledger_entries.shop_id', $shopId);
        } elseif ($restrictToShopIds !== null) {
            $query->whereIn('commission_ledger_entries.shop_id', $restrictToShopIds);
        }

        return $query;
    }

    /**
     * @param  int[]|null  $restrictToShopIds
     * @return array{data: array<int, array<string, mixed>>, current_page:int, last_page:int, per_page:int, total:int}
     */
    public function paginate(
        ?int $staffId,
        ?int $shopId,
        ?string $fromDate,
        ?string $toDate,
        int $page,
        int $perPage = 20,
        ?array $restrictToShopIds = null,
    ): array {
        $query = CommissionLedgerEntryRecord::query();

        if ($shopId !== null) {
            $query->where('shop_id', $shopId);
        } elseif ($restrictToShopIds !== null) {
            $query->whereIn('shop_id', $restrictToShopIds);
        }

        if ($staffId !== null) {
            $query->where('staff_id', $staffId);
        }

        if ($fromDate !== null && $fromDate !== '') {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate !== null && $toDate !== '') {
            $query->whereDate('created_at', '<=', $toDate);
        }

        $paginator = $query->orderByDesc('created_at')->paginate($perPage, ['*'], 'page', $page);
        $entries = $paginator->getCollection();

        $staffNames = StaffRecord::query()
            ->whereIn('id', $entries->pluck('staff_id')->unique()->all())
            ->pluck('name', 'id');

        return [
            'data' => $entries->map(static fn (CommissionLedgerEntryRecord $entry): array => [
                'id' => $entry->id,
                'staff_id' => $entry->staff_id,
                'staff_name' => $staffNames[$entry->staff_id] ?? 'Unknown',
                'role_snapshot' => $entry->role_snapshot,
                'source_type' => $entry->source_type,
                'source_id' => $entry->source_id,
                'commissionable_amount_minor' => $entry->commissionable_amount_minor,
                'entry_type' => $entry->entry_type,
                'status' => $entry->status,
                'shop_id' => $entry->shop_id,
                'created_at' => $entry->created_at->toIso8601String(),
            ])->all(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    /** Backs a staff member's own commission total for a date range (e.g. a payroll-period summary). */
    public function totalForStaff(int $staffId, ?string $fromDate, ?string $toDate): int
    {
        $query = CommissionLedgerEntryRecord::query()->where('staff_id', $staffId);

        if ($fromDate !== null && $fromDate !== '') {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate !== null && $toDate !== '') {
            $query->whereDate('created_at', '<=', $toDate);
        }

        return (int) $query->sum('commissionable_amount_minor');
    }
}
