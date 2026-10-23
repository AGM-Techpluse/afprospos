<?php

declare(strict_types=1);

namespace Domain\Payments\Application\Queries;

use Domain\Identity\Application\Contracts\CustomerDirectoryQuery;
use Domain\Payments\Infrastructure\Persistence\Eloquent\PaymentTransactionRecord;
use Domain\Repair\Application\Contracts\RepairJobLookup;
use Domain\Sales\Application\Contracts\CheckoutLookup;

/** Backs Admin Payments/Index — the reconciliation/dispute queue, filterable by status/method/payable type. */
final class PaymentTransactionsQuery
{
    public function __construct(
        private readonly RepairJobLookup $repairJobs,
        private readonly CheckoutLookup $checkouts,
        private readonly CustomerDirectoryQuery $customers,
    ) {}

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
            'purpose' => $transaction->purpose,
            'method' => $transaction->method,
            'amount_minor' => $transaction->amount_minor,
            'status' => $transaction->status,
            'provider_reference' => $transaction->provider_reference,
            'created_at' => $transaction->created_at->toIso8601String(),
            'source_label' => $this->describeSource($transaction->payable_type, $transaction->payable_id),
            'customer_name' => $this->customerName($transaction->payable_type, $transaction->payable_id),
        ];
    }

    private function describeSource(string $payableType, int $payableId): string
    {
        if ($payableType === 'repair_job') {
            $job = $this->repairJobs->find($payableId);

            if ($job === null) {
                return "Repair #{$payableId}";
            }

            return "Repair #{$payableId} — {$job['device_make']} {$job['device_model']}";
        }

        return "Sale checkout #{$payableId}";
    }

    /** A customer column of its own — same identity whether this payment is for a repair or a sale, instead of buried inside the free-text source label (or, for a sale checkout, missing entirely). */
    private function customerName(string $payableType, int $payableId): ?string
    {
        if ($payableType === 'repair_job') {
            $job = $this->repairJobs->find($payableId);

            if ($job === null) {
                return null;
            }

            return $job['customer_id'] !== null ? $this->customers->find($job['customer_id'])['name'] ?? null : null;
        }

        $checkout = $this->checkouts->find($payableId);

        if ($checkout === null) {
            return null;
        }

        return $checkout['customer_id'] !== null
            ? $this->customers->find($checkout['customer_id'])['name'] ?? null
            : $checkout['walk_in_name'];
    }
}
