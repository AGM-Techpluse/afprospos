<?php

declare(strict_types=1);

namespace Domain\Payments\Infrastructure\Persistence\Repositories;

use Domain\Payments\Domain\Entities\PaymentRefund;
use Domain\Payments\Domain\Repositories\PaymentRefundRepository;
use Domain\Payments\Domain\ValueObjects\PaymentRefundId;
use Domain\Payments\Domain\ValueObjects\PaymentTransactionId;
use Domain\Payments\Infrastructure\Persistence\Eloquent\PaymentRefundRecord;
use Domain\Shared\Domain\ValueObjects\Money;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class EloquentPaymentRefundRepository implements PaymentRefundRepository
{
    public function append(PaymentRefund $refund): PaymentRefundId
    {
        $record = PaymentRefundRecord::query()->create([
            'payment_transaction_id' => $refund->paymentTransactionId()->value,
            'amount_minor' => $refund->amount()->minor,
            'refunded_by_staff_id' => $refund->refundedByStaffId()?->value,
            'source' => $refund->source(),
        ]);

        return new PaymentRefundId($record->id);
    }

    public function totalRefundedMinor(PaymentTransactionId $paymentTransactionId): int
    {
        return (int) PaymentRefundRecord::query()
            ->where('payment_transaction_id', $paymentTransactionId->value)
            ->sum('amount_minor');
    }

    public function listForTransaction(PaymentTransactionId $paymentTransactionId): array
    {
        return PaymentRefundRecord::query()
            ->where('payment_transaction_id', $paymentTransactionId->value)
            ->orderBy('created_at')
            ->get()
            ->map(fn (PaymentRefundRecord $record): PaymentRefund => PaymentRefund::reconstitute(
                new PaymentRefundId($record->id),
                $paymentTransactionId,
                new Money($record->amount_minor),
                $record->refunded_by_staff_id !== null ? new StaffId($record->refunded_by_staff_id) : null,
                $record->source,
                $record->created_at->toImmutable(),
            ))
            ->all();
    }
}
