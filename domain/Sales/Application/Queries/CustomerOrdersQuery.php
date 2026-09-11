<?php

declare(strict_types=1);

namespace Domain\Sales\Application\Queries;

use Domain\Sales\Infrastructure\Persistence\Eloquent\SaleRecord;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SalesCheckoutRecord;
use Illuminate\Support\Collection;

/** Backs Customer Orders/Index — a customer's own open checkouts + completed sales, unioned with a `type` discriminator. Small per-customer volume, so paginated in PHP rather than a SQL UNION. */
final class CustomerOrdersQuery
{
    /** @return array{data: array<int, array<string, mixed>>, current_page:int, last_page:int, per_page:int, total:int} */
    public function paginate(int $customerId, int $page, int $perPage = 20): array
    {
        $checkouts = SalesCheckoutRecord::query()
            ->where('customer_id', $customerId)
            ->where('status', 'open')
            ->get()
            ->map(static fn (SalesCheckoutRecord $checkout): array => [
                'type' => 'checkout',
                'id' => $checkout->id,
                'status' => $checkout->status,
                'total_minor' => $checkout->total_minor,
                'reservation_expires_at' => $checkout->reservation_expires_at->toIso8601String(),
                'created_at' => $checkout->created_at->toIso8601String(),
            ]);

        $sales = SaleRecord::query()
            ->where('customer_id', $customerId)
            ->get()
            ->map(static fn (SaleRecord $sale): array => [
                'type' => 'sale',
                'id' => $sale->id,
                'status' => 'paid',
                'total_minor' => $sale->total_minor,
                'invoice_number' => $sale->invoice_number,
                'created_at' => $sale->created_at->toIso8601String(),
            ]);

        /** @var Collection<int, array<string, mixed>> $all */
        $all = $checkouts->concat($sales)->sortByDesc('created_at')->values();

        $total = $all->count();
        $items = $all->forPage($page, $perPage)->values();

        return [
            'data' => $items->all(),
            'current_page' => $page,
            'last_page' => (int) max(1, ceil($total / $perPage)),
            'per_page' => $perPage,
            'total' => $total,
        ];
    }
}
