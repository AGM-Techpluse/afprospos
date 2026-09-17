<?php

declare(strict_types=1);

namespace Domain\Warranty\Application\Queries;

use Domain\Warranty\Infrastructure\Persistence\Eloquent\WarrantyClaimRecord;

/** Backs Customer Warranty/Claims/Index — scoped to one customer's own claims. */
final class CustomerWarrantyClaimsQuery
{
    /** @return array{data: array<int, array<string, mixed>>, current_page:int, last_page:int, per_page:int, total:int} */
    public function paginate(int $customerId, int $page, int $perPage = 20): array
    {
        $paginator = WarrantyClaimRecord::query()
            ->where('customer_id', $customerId)
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $paginator->getCollection()->map(fn (WarrantyClaimRecord $claim): array => [
                'id' => $claim->id,
                'resolution_state' => $claim->resolution_state,
                'selected_remedy' => $claim->selected_remedy,
                'created_at' => $claim->created_at->toIso8601String(),
            ])->all(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}
