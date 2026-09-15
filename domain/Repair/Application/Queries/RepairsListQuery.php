<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Queries;

use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;

/** Backs Admin Repairs/Index — search/filter/pagination per the standing table-pages rule. */
final class RepairsListQuery
{
    /** @return array{data: array<int, array<string, mixed>>, current_page:int, last_page:int, per_page:int, total:int} */
    public function paginate(?string $status, ?int $shopId, int $page, int $perPage = 20): array
    {
        $query = RepairJobRecord::query();

        if ($status !== null && $status !== '') {
            $query->where('repair_status', $status);
        }

        if ($shopId !== null) {
            $query->where('shop_id', $shopId);
        }

        $paginator = $query->orderByDesc('created_at')->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $paginator->getCollection()->map(fn (RepairJobRecord $job): array => $this->toArray($job))->all(),
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

        return $query->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (RepairJobRecord $job): array => $this->toArray($job))
            ->all();
    }

    /** @return array<string, mixed> */
    private function toArray(RepairJobRecord $job): array
    {
        return [
            'id' => $job->id,
            'shop_id' => $job->shop_id,
            'customer_id' => $job->customer_id,
            'device_make' => $job->device_make,
            'device_model' => $job->device_model,
            'technician_staff_id' => $job->technician_staff_id,
            'repair_status' => $job->repair_status,
            'financial_status' => $job->financial_status,
            'created_at' => $job->created_at->toIso8601String(),
        ];
    }
}
