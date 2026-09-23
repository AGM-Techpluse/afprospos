<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Queries;

use Domain\Identity\Application\Contracts\CustomerDirectoryQuery;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;

/** Backs Admin Repairs/Index — search/filter/pagination per the standing table-pages rule. */
final class RepairsListQuery
{
    public function __construct(private readonly CustomerDirectoryQuery $customers) {}

    /**
     * `$restrictToShopIds` narrows an "all shops" view (no explicit
     * `$shopId`) to a non-owner staff member's actual grants — passed by
     * every caller except the Owner's, closing off both the "All shops"
     * filter option and a crafted `?shop_id=` bypass at once.
     *
     * @param  int[]|null  $restrictToShopIds
     * @return array{data: array<int, array<string, mixed>>, current_page:int, last_page:int, per_page:int, total:int}
     */
    public function paginate(?string $status, ?int $shopId, int $page, int $perPage = 20, ?array $restrictToShopIds = null): array
    {
        $query = RepairJobRecord::query();

        if ($status !== null && $status !== '') {
            $query->where('repair_status', $status);
        }

        if ($shopId !== null) {
            $query->where('shop_id', $shopId);
        } elseif ($restrictToShopIds !== null) {
            $query->whereIn('shop_id', $restrictToShopIds);
        }

        $paginator = $query->orderByDesc('created_at')->paginate($perPage, ['*'], 'page', $page);
        $jobs = $paginator->getCollection();
        $customers = $this->customers->findMany($jobs->pluck('customer_id')->unique()->all());

        return [
            'data' => $jobs->map(fn (RepairJobRecord $job): array => $this->toArray($job, $customers))->all(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    /** Backs the Admin Dashboard's "Open repairs" stat card — anything not yet in a terminal state. */
    public function countOpen(?int $shopId): int
    {
        $query = RepairJobRecord::query()
            ->whereNotIn('repair_status', ['completed', 'unrepairable', 'expired_cancelled']);

        if ($shopId !== null) {
            $query->where('shop_id', $shopId);
        }

        return $query->count();
    }

    /** @return array<int, array<string, mixed>> */
    public function recent(?int $shopId, int $limit = 5): array
    {
        $query = RepairJobRecord::query();

        if ($shopId !== null) {
            $query->where('shop_id', $shopId);
        }

        $jobs = $query->orderByDesc('created_at')->limit($limit)->get();
        $customers = $this->customers->findMany($jobs->pluck('customer_id')->unique()->all());

        return $jobs->map(fn (RepairJobRecord $job): array => $this->toArray($job, $customers))->all();
    }

    /**
     * Backs the CSV export — same filters as paginate() minus pagination,
     * or an explicit set of IDs (the "export selected" mode), never both.
     *
     * `$restrictToShopIds` applies regardless of `$ids` vs. filter mode —
     * an "export selected" request naming an out-of-scope repair id must
     * not leak it just because it skipped the status/shop filter branch.
     *
     * @param  int[]|null  $ids
     * @param  int[]|null  $restrictToShopIds
     * @return array<int, array<string, mixed>>
     */
    public function exportRows(?string $status, ?int $shopId, ?array $ids, ?array $restrictToShopIds = null): array
    {
        $query = RepairJobRecord::query();

        if ($ids !== null) {
            $query->whereIn('id', $ids);
        } else {
            if ($status !== null && $status !== '') {
                $query->where('repair_status', $status);
            }

            if ($shopId !== null) {
                $query->where('shop_id', $shopId);
            }
        }

        if ($restrictToShopIds !== null) {
            $query->whereIn('shop_id', $restrictToShopIds);
        }

        $jobs = $query->orderByDesc('created_at')->get();
        $customers = $this->customers->findMany($jobs->pluck('customer_id')->unique()->all());
        $technicianNames = StaffRecord::query()
            ->whereIn('id', $jobs->pluck('technician_staff_id')->filter()->unique()->all())
            ->pluck('name', 'id');

        return $jobs->map(fn (RepairJobRecord $job): array => [
            ...$this->toArray($job, $customers),
            'technician_name' => $job->technician_staff_id !== null ? ($technicianNames[$job->technician_staff_id] ?? null) : null,
        ])->all();
    }

    /**
     * @param  array<int, array{id: int, name: string, email: ?string, phone: string}>  $customers
     * @return array<string, mixed>
     */
    private function toArray(RepairJobRecord $job, array $customers): array
    {
        return [
            'id' => $job->id,
            'shop_id' => $job->shop_id,
            'customer_id' => $job->customer_id,
            'customer_name' => $customers[$job->customer_id]['name'] ?? null,
            'device_make' => $job->device_make,
            'device_model' => $job->device_model,
            'technician_staff_id' => $job->technician_staff_id,
            'labour_charge_minor' => $job->labour_charge_minor,
            'repair_status' => $job->repair_status,
            'financial_status' => $job->financial_status,
            'created_at' => $job->created_at->toIso8601String(),
            'updated_at' => $job->updated_at->toIso8601String(),
        ];
    }
}
