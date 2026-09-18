<?php

declare(strict_types=1);

namespace Domain\Warranty\Application\Queries;

use Domain\Warranty\Infrastructure\Persistence\Eloquent\ReturnRequestRecord;

/** Backs Admin Warranty/Returns/Index — mirrors WarrantyClaimQueueQuery exactly. */
final class ReturnRequestQueueQuery
{
    /** @return array{data: array<int, array<string, mixed>>, current_page:int, last_page:int, per_page:int, total:int} */
    public function paginate(?string $search, ?string $status, int $page, int $perPage = 20): array
    {
        $query = ReturnRequestRecord::query()->with('customer');

        if ($search !== null && $search !== '') {
            $query->whereHas('customer', function ($customerQuery) use ($search): void {
                $customerQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($status !== null && $status !== '') {
            $query->where('resolution_state', $status);
        }

        $paginator = $query->orderByDesc('created_at')->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $paginator->getCollection()->map(fn (ReturnRequestRecord $returnRequest): array => [
                'id' => $returnRequest->id,
                'sale_id' => $returnRequest->sale_id,
                'customer' => $returnRequest->customer === null ? null : ['name' => $returnRequest->customer->name, 'phone' => $returnRequest->customer->phone],
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
