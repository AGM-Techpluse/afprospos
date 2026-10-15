<?php

declare(strict_types=1);

namespace Domain\Commission\Application\Queries;

use Domain\Commission\Infrastructure\Persistence\Eloquent\CommissionLedgerEntryRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;

/** Backs Admin Commission/Index — paginated, filterable per the standing table-pages rule. */
final class CommissionReportQuery
{
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
