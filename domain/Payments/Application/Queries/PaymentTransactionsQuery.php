<?php

declare(strict_types=1);

namespace Domain\Payments\Application\Queries;

use Domain\Payments\Infrastructure\Persistence\Eloquent\PaymentTransactionRecord;

/** Backs Admin Payments/Index — the reconciliation/dispute queue, filterable by status/method/payable type. */
final class PaymentTransactionsQuery
{
    /** @return array{data: array<int, array<string, mixed>>, current_page:int, last_page:int, per_page:int, total:int} */
    public function paginate(?string $status, ?string $method, int $page, int $perPage = 20): array
    {
        $query = PaymentTransactionRecord::query();

        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        if ($method !== null && $method !== '') {
            $query->where('method', $method);
        }

        $paginator = $query->orderByDesc('created_at')->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $paginator->getCollection()->map(fn (PaymentTransactionRecord $transaction): array => $this->toArray($transaction))->all(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    /** Backs the Admin Dashboard's "Pending disputes" stat card. No shop scoping — payments_transactions has no shop_id column (it's a standalone bounded context). */
    public function countByStatus(string $status): int
    {
        return PaymentTransactionRecord::query()->where('status', $status)->count();
    }

    /**
     * Backs the CSV export — same filters as paginate() minus pagination,
     * or an explicit set of IDs (the "export selected" mode), never both.
     *
     * @param  int[]|null  $ids
     * @return array<int, array<string, mixed>>
     */
    public function exportRows(?string $status, ?string $method, ?array $ids): array
    {
        $query = PaymentTransactionRecord::query();

        if ($ids !== null) {
            $query->whereIn('id', $ids);
        } else {
            if ($status !== null && $status !== '') {
                $query->where('status', $status);
            }

            if ($method !== null && $method !== '') {
                $query->where('method', $method);
            }
        }

        return $query->orderByDesc('created_at')->get()
            ->map(fn (PaymentTransactionRecord $transaction): array => $this->toArray($transaction))
            ->all();
    }

    /** @return array<string, mixed> */
    private function toArray(PaymentTransactionRecord $transaction): array
    {
        return [
            'id' => $transaction->id,
            'payable_type' => $transaction->payable_type,
            'payable_id' => $transaction->payable_id,
            'method' => $transaction->method,
            'amount_minor' => $transaction->amount_minor,
            'status' => $transaction->status,
            'provider_reference' => $transaction->provider_reference,
            'created_at' => $transaction->created_at->toIso8601String(),
        ];
    }
}
