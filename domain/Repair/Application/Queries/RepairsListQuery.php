<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Queries;

use Domain\Identity\Application\Contracts\CustomerDirectoryQuery;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;

/** Backs Admin Repairs/Index — search/filter/pagination per the standing table-pages rule. */
final class RepairsListQuery
{
    public function __construct(private readonly CustomerDirectoryQuery $customers) {}

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
            'repair_status' => $job->repair_status,
            'financial_status' => $job->financial_status,
            'created_at' => $job->created_at->toIso8601String(),
        ];
    }
}
