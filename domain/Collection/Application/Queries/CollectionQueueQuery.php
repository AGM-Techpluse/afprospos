<?php

declare(strict_types=1);

namespace Domain\Collection\Application\Queries;

use Domain\Collection\Infrastructure\Persistence\Eloquent\CollectionCaseRecord;

/** Backs Admin Collection/Index — the pending/overdue/abandoned queue, filterable by status/shop. */
final class CollectionQueueQuery
{
    /** @return array{data: array<int, array<string, mixed>>, current_page:int, last_page:int, per_page:int, total:int} */
    public function paginate(?string $status, ?int $shopId, int $page, int $perPage = 20): array
    {
        $query = CollectionCaseRecord::query();

        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        if ($shopId !== null) {
            $query->where('shop_id', $shopId);
        }

        $paginator = $query->orderBy('collection_deadline_at')->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $paginator->getCollection()->map(fn (CollectionCaseRecord $case): array => $this->toArray($case))->all(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    /** @return array<string, mixed> */
    private function toArray(CollectionCaseRecord $case): array
    {
        return [
            'id' => $case->id,
            'source_type' => $case->source_type,
            'source_id' => $case->source_id,
            'shop_id' => $case->shop_id,
            'context' => $case->context,
            'status' => $case->status,
            'collection_deadline_at' => $case->collection_deadline_at->toIso8601String(),
            'accrued_storage_fee_minor' => $case->accrued_storage_fee_minor,
        ];
    }
}
