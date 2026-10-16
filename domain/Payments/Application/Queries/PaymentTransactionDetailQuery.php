<?php

declare(strict_types=1);

namespace Domain\Payments\Application\Queries;

use Domain\Identity\Application\Contracts\CustomerDirectoryQuery;
use Domain\Identity\Application\Queries\StaffDirectoryQuery;
use Domain\Payments\Domain\Entities\PaymentRefund;
use Domain\Payments\Domain\Repositories\PaymentRefundRepository;
use Domain\Payments\Domain\ValueObjects\PaymentTransactionId;
use Domain\Payments\Infrastructure\Persistence\Eloquent\PaymentTransactionRecord;
use Domain\Repair\Application\Contracts\RepairJobLookup;

final class PaymentTransactionDetailQuery
{
    public function __construct(
        private readonly StaffDirectoryQuery $staff,
        private readonly RepairJobLookup $repairJobs,
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
                ? "Repair #{$payableId} — {$job['device_make']} {$job['device_model']}".($customerName !== null ? " ({$customerName})" : '')
                : "Repair #{$payableId}";

            return ['label' => $label, 'link' => "/admin/repairs/{$payableId}"];
        }

        return ['label' => "Sale checkout #{$payableId}", 'link' => "/admin/sales/checkout?checkout={$payableId}"];
    }
}
