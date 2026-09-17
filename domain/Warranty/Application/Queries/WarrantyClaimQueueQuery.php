<?php

declare(strict_types=1);

namespace Domain\Warranty\Application\Queries;

use Domain\Warranty\Infrastructure\Persistence\Eloquent\WarrantyClaimRecord;

/** Backs Admin Warranty/Claims/Index — searchable by customer name/phone (`customers` is Shared Kernel, CPNC §0.2), filterable by resolution state, real pagination. */
final class WarrantyClaimQueueQuery
{
    /** @return array{data: array<int, array<string, mixed>>, current_page:int, last_page:int, per_page:int, total:int} */
    public function paginate(?string $search, ?string $status, int $page, int $perPage = 20): array
    {
        $query = WarrantyClaimRecord::query()->with('customer');

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
            'data' => $paginator->getCollection()->map(fn (WarrantyClaimRecord $claim): array => [
                'id' => $claim->id,
                'customer' => $claim->customer === null ? null : ['name' => $claim->customer->name, 'phone' => $claim->customer->phone],
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
