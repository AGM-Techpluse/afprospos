<?php

declare(strict_types=1);

namespace Domain\Warranty\Application\Queries;

use Domain\Warranty\Infrastructure\Persistence\Eloquent\TradeInAssessmentRecord;

/** Backs Admin Warranty/TradeIns/Index — mirrors WarrantyClaimQueueQuery. Staff-only, no customer-facing equivalent. */
final class TradeInAssessmentQueueQuery
{
    /** @return array{data: array<int, array<string, mixed>>, current_page:int, last_page:int, per_page:int, total:int} */
    public function paginate(?string $search, ?string $status, int $page, int $perPage = 20): array
    {
        $query = TradeInAssessmentRecord::query()->with('customer');

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
            'data' => $paginator->getCollection()->map(fn (TradeInAssessmentRecord $tradeIn): array => [
                'id' => $tradeIn->id,
                'customer' => $tradeIn->customer === null ? null : ['name' => $tradeIn->customer->name, 'phone' => $tradeIn->customer->phone],
                'device_description' => $tradeIn->device_description,
                'assessed_value_minor' => $tradeIn->assessed_value_minor,
                'resolution_state' => $tradeIn->resolution_state,
                'created_at' => $tradeIn->created_at->toIso8601String(),
            ])->all(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    /** @return array<string, mixed>|null */
    public function find(int $tradeInAssessmentId): ?array
    {
        $tradeIn = TradeInAssessmentRecord::query()->with('customer')->find($tradeInAssessmentId);

        if ($tradeIn === null) {
            return null;
        }

        return [
            'id' => $tradeIn->id,
            'customer_id' => $tradeIn->customer_id,
            'customer' => $tradeIn->customer === null ? null : ['name' => $tradeIn->customer->name, 'phone' => $tradeIn->customer->phone],
            'related_checkout_id' => $tradeIn->related_checkout_id,
            'device_description' => $tradeIn->device_description,
            'assessed_value_minor' => $tradeIn->assessed_value_minor,
            'resolution_state' => $tradeIn->resolution_state,
            'assessed_by_staff_id' => $tradeIn->assessed_by_staff_id,
            'approved_by_staff_id' => $tradeIn->approved_by_staff_id,
            'created_at' => $tradeIn->created_at->toIso8601String(),
        ];
    }
}
