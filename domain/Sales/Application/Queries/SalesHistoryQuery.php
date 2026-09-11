<?php

declare(strict_types=1);

namespace Domain\Sales\Application\Queries;

use Domain\Sales\Infrastructure\Persistence\Eloquent\SaleRecord;

/** Backs Admin Sales/Index — paginated, search/filter per the standing table-pages rule. */
final class SalesHistoryQuery
{
    /** @return array{data: array<int, array<string, mixed>>, current_page:int, last_page:int, per_page:int, total:int} */
    public function paginate(?string $search, ?int $shopId, int $page, int $perPage = 20): array
    {
        $query = SaleRecord::query();

        if ($shopId !== null) {
            $query->where('shop_id', $shopId);
        }

        if ($search !== null && $search !== '') {
            $query->where('invoice_number', 'like', "%{$search}%");
        }

        $paginator = $query->orderByDesc('created_at')->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $paginator->getCollection()->map(static fn (SaleRecord $sale): array => [
                'id' => $sale->id,
                'shop_id' => $sale->shop_id,
                'customer_id' => $sale->customer_id,
                'total_minor' => $sale->total_minor,
                'payment_method' => $sale->payment_method,
                'invoice_number' => $sale->invoice_number,
                'created_at' => $sale->created_at->toIso8601String(),
            ])->all(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}
