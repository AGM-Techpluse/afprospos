<?php

declare(strict_types=1);

namespace Domain\Payments\Application\Queries;

use Domain\Identity\Application\Contracts\CustomerDirectoryQuery;
use Domain\Identity\Application\Queries\StaffDirectoryQuery;
use Domain\Payments\Domain\Entities\PaymentRefund;
use Domain\Payments\Domain\Repositories\PaymentRefundRepository;
use Domain\Payments\Domain\ValueObjects\PaymentTransactionId;
use Domain\Payments\Infrastructure\Persistence\Eloquent\PaymentTransactionRecord;
use Domain\Repair\Application\Contracts\DiagnosisCaseLookup;
use Domain\Repair\Application\Contracts\RepairJobLookup;
use Domain\Sales\Application\Contracts\CheckoutLookup;

final class PaymentTransactionDetailQuery
{
    public function __construct(
        private readonly StaffDirectoryQuery $staff,
        private readonly RepairJobLookup $repairJobs,
        private readonly DiagnosisCaseLookup $diagnosisCases,
        private readonly CheckoutLookup $checkouts,
        private readonly CustomerDirectoryQuery $customers,
        private readonly PaymentRefundRepository $refunds,
    ) {}

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $transaction = PaymentTransactionRecord::query()->find($id);

        if ($transaction === null) {
            return null;
        }

        $transactionId = new PaymentTransactionId($transaction->id);
        $refunds = $this->refunds->listForTransaction($transactionId);
        $refundedAmountMinor = array_sum(array_map(static fn (PaymentRefund $refund): int => $refund->amount()->minor, $refunds));

        return [
            'id' => $transaction->id,
            'payable_type' => $transaction->payable_type,
            'payable_id' => $transaction->payable_id,
            'purpose' => $transaction->purpose,
            'method' => $transaction->method,
            'amount_minor' => $transaction->amount_minor,
            'status' => $transaction->status,
            'provider_reference' => $transaction->provider_reference,
            'confirmed_by_staff_id' => $transaction->confirmed_by_staff_id,
            'confirmed_by_staff_name' => $transaction->confirmed_by_staff_id !== null
                ? ($this->staff->find($transaction->confirmed_by_staff_id)['name'] ?? null)
                : null,
            'dispute_opened_at' => $transaction->dispute_opened_at?->toIso8601String(),
            'dispute_proof_reference' => $transaction->dispute_proof_reference,
            'dispute_resolved_by_staff_id' => $transaction->dispute_resolved_by_staff_id,
            'dispute_resolved_by_staff_name' => $transaction->dispute_resolved_by_staff_id !== null
                ? ($this->staff->find($transaction->dispute_resolved_by_staff_id)['name'] ?? null)
                : null,
            'created_at' => $transaction->created_at->toIso8601String(),
            'source' => $this->describeSource($transaction->payable_type, $transaction->payable_id),
            'customer_name' => $this->customerName($transaction->payable_type, $transaction->payable_id),
            'sale' => $transaction->payable_type === 'sales_checkout' ? $this->saleDetail($transaction->payable_id) : null,
            'refunded_amount_minor' => $refundedAmountMinor,
            'remaining_refundable_minor' => $transaction->amount_minor - $refundedAmountMinor,
            'refunds' => array_map(fn (PaymentRefund $refund): array => [
                'amount_minor' => $refund->amount()->minor,
                'refunded_by_staff_name' => $refund->refundedByStaffId() !== null
                    ? ($this->staff->find($refund->refundedByStaffId()->value)['name'] ?? null)
                    : null,
                'source' => $refund->source(),
                'created_at' => $refund->createdAt()?->toIso8601String(),
            ], $refunds),
        ];
    }

    /** @return array{label: string, link: string} */
    private function describeSource(string $payableType, int $payableId): array
    {
        if ($payableType === 'repair_job') {
            $job = $this->repairJobs->find($payableId);
            $customerName = $job !== null && $job['customer_id'] !== null
                ? $this->customers->find($job['customer_id'])['name'] ?? null
                : null;

            $label = $job !== null
                ? "Repair ◉{$payableId} — {$job['device_make']} {$job['device_model']}".($customerName !== null ? " ({$customerName})" : '')
                : "Repair ◉{$payableId}";

            return ['label' => $label, 'link' => "/admin/repairs/{$payableId}"];
        }

        if ($payableType === 'diagnosis_case') {
            $case = $this->diagnosisCases->find($payableId);

            $label = $case !== null
                ? "Diagnosis ◉{$payableId} — {$case['device_make']} {$case['device_model']}"
                : "Diagnosis ◉{$payableId}";

            return ['label' => $label, 'link' => "/admin/repairs/diagnosis-cases/{$payableId}"];
        }

        return ['label' => "Sale checkout ◉{$payableId}", 'link' => "/admin/sales/checkout?checkout={$payableId}"];
    }

    /** Same identity whether this payment is for a repair, a diagnosis, or a sale — a dedicated field instead of buried inside the free-text source label. */
    private function customerName(string $payableType, int $payableId): ?string
    {
        if ($payableType === 'repair_job') {
            $job = $this->repairJobs->find($payableId);

            return $job !== null && $job['customer_id'] !== null
                ? $this->customers->find($job['customer_id'])['name'] ?? null
                : null;
        }

        if ($payableType === 'diagnosis_case') {
            $case = $this->diagnosisCases->find($payableId);

            if ($case === null) {
                return null;
            }

            return $case['customer_id'] !== null
                ? $this->customers->find($case['customer_id'])['name'] ?? null
                : $case['walk_in_name'];
        }

        $checkout = $this->checkouts->find($payableId);

        if ($checkout === null) {
            return null;
        }

        return $checkout['customer_id'] !== null
            ? $this->customers->find($checkout['customer_id'])['name'] ?? null
            : $checkout['walk_in_name'];
    }

    /**
     * What was actually bought — the line items, and whether a discount was applied — for
     * a sale payment's detail page. Null payable_id lookups (e.g. the checkout record was
     * somehow removed) simply omit the section rather than erroring.
     *
     * @return array{subtotal_minor:int, discount_minor:int, total_minor:int, items: array<int, array<string, mixed>>}|null
     */
    private function saleDetail(int $checkoutId): ?array
    {
        $checkout = $this->checkouts->find($checkoutId);

        if ($checkout === null) {
            return null;
        }

        return [
            'subtotal_minor' => $checkout['subtotal_minor'],
            'discount_minor' => $checkout['discount_minor'],
            'total_minor' => $checkout['total_minor'],
            'items' => $checkout['items'],
        ];
    }
}
