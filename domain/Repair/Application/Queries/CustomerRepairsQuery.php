<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Queries;

use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;

/** Backs Customer Repairs/Index — read-only, scoped to the authenticated customer (mirrors CustomerOrdersQuery's role for Sales). */
final class CustomerRepairsQuery
{
    /** @return array{data: array<int, array<string, mixed>>, current_page:int, last_page:int, per_page:int, total:int} */
    public function paginate(int $customerId, int $page, int $perPage = 20): array
    {
        $paginator = RepairJobRecord::query()
            ->where('customer_id', $customerId)
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $paginator->getCollection()->map(static fn (RepairJobRecord $job): array => [
                'id' => $job->id,
                'device_make' => $job->device_make,
                'device_model' => $job->device_model,
                'repair_status' => $job->repair_status,
                'financial_status' => $job->financial_status,
                'labour_charge_minor' => $job->labour_charge_minor,
                'estimated_collection_date' => $job->estimated_collection_date?->toDateString(),
                'created_at' => $job->created_at->toIso8601String(),
            ])->all(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}
