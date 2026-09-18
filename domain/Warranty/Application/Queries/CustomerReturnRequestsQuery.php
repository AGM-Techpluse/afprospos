<?php

declare(strict_types=1);

namespace Domain\Warranty\Application\Queries;

use Domain\Warranty\Infrastructure\Persistence\Eloquent\ReturnRequestRecord;

/** Backs Customer Warranty/Returns/Index — scoped to one customer's own returns, mirrors CustomerWarrantyClaimsQuery. */
final class CustomerReturnRequestsQuery
{
    /** @return array{data: array<int, array<string, mixed>>, current_page:int, last_page:int, per_page:int, total:int} */
    public function paginate(int $customerId, int $page, int $perPage = 20): array
    {
        $paginator = ReturnRequestRecord::query()
            ->where('customer_id', $customerId)
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $paginator->getCollection()->map(fn (ReturnRequestRecord $returnRequest): array => [
                'id' => $returnRequest->id,
                'sale_id' => $returnRequest->sale_id,
                'resolution_state' => $returnRequest->resolution_state,
                'return_window_expires_at' => $returnRequest->return_window_expires_at->toIso8601String(),
                'created_at' => $returnRequest->created_at->toIso8601String(),
            ])->all(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}
