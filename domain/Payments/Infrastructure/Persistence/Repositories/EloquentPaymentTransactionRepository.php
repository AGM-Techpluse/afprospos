<?php

declare(strict_types=1);

namespace Domain\Payments\Infrastructure\Persistence\Repositories;

use Domain\Payments\Domain\Entities\PaymentTransaction;
use Domain\Payments\Domain\Repositories\PaymentTransactionRepository;
use Domain\Payments\Domain\ValueObjects\PayableType;
use Domain\Payments\Domain\ValueObjects\PaymentMethod;
use Domain\Payments\Domain\ValueObjects\PaymentTransactionId;
use Domain\Payments\Infrastructure\Persistence\Eloquent\PaymentTransactionRecord;
use Domain\Shared\Domain\ValueObjects\Money;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class EloquentPaymentTransactionRepository implements PaymentTransactionRepository
{
    public function get(PaymentTransactionId $id): PaymentTransaction
    {
        return $this->toDomain(PaymentTransactionRecord::query()->findOrFail($id->value));
    }

    /** Locks the transaction row (mirrors EloquentSalesCheckoutRepository::lockForUpdate) — the serialization point for a duplicate confirm/dispute/refund race. */
    public function lockForUpdate(PaymentTransactionId $id): PaymentTransaction
    {
        $record = PaymentTransactionRecord::query()->whereKey($id->value)->lockForUpdate()->firstOrFail();

        return $this->toDomain($record);
    }

    public function save(PaymentTransaction $transaction): PaymentTransactionId
    {
        $attributes = [
            'payable_type' => $transaction->payableType()->value,
            'payable_id' => $transaction->payableId(),
            'method' => $transaction->method()->value,
            'amount_minor' => $transaction->amount()->minor,
            'status' => $transaction->status(),
            'provider_reference' => $transaction->providerReference(),
            'confirmed_by_staff_id' => $transaction->confirmedByStaffId()?->value,
            'dispute_opened_at' => $transaction->disputeOpenedAt(),
            'dispute_proof_reference' => $transaction->disputeProofReference(),
            'dispute_resolved_by_staff_id' => $transaction->disputeResolvedByStaffId()?->value,
        ];

        if ($transaction->id() === null) {
            $record = PaymentTransactionRecord::query()->create($attributes);
        } else {
            $record = PaymentTransactionRecord::query()->findOrFail($transaction->id()->value);
            $record->update($attributes);
        }

        return new PaymentTransactionId($record->id);
    }

    public function findByPayable(PayableType $payableType, int $payableId): array
    {
        return PaymentTransactionRecord::query()
            ->where('payable_type', $payableType->value)
            ->where('payable_id', $payableId)
            ->get()
            ->map(fn (PaymentTransactionRecord $record): PaymentTransaction => $this->toDomain($record))
            ->all();
    }

    private function toDomain(PaymentTransactionRecord $record): PaymentTransaction
    {
        return PaymentTransaction::reconstitute(
            new PaymentTransactionId($record->id),
            new PayableType($record->payable_type),
            $record->payable_id,
            new PaymentMethod($record->method),
            new Money($record->amount_minor),
            $record->status,
            $record->provider_reference,
            $record->confirmed_by_staff_id !== null ? new StaffId($record->confirmed_by_staff_id) : null,
            $record->dispute_opened_at?->toDateTimeImmutable(),
            $record->dispute_proof_reference,
            $record->dispute_resolved_by_staff_id !== null ? new StaffId($record->dispute_resolved_by_staff_id) : null,
        );
    }
}
