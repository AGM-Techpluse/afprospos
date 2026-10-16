<?php

declare(strict_types=1);

namespace Domain\Payments\Infrastructure\Laravel;

use DateTimeInterface;
use Domain\Payments\Application\Contracts\PaymentRevenueReader;
use Domain\Payments\Infrastructure\Persistence\Eloquent\PaymentRefundRecord;
use Domain\Payments\Infrastructure\Persistence\Eloquent\PaymentTransactionRecord;

final class EloquentPaymentRevenueReader implements PaymentRevenueReader
{
    public function confirmedRevenueMinor(string $payableType, array $payableIds): int
    {
        $transactions = PaymentTransactionRecord::query()
            ->where('payable_type', $payableType)
            ->whereIn('status', ['confirmed', 'partially_refunded'])
            ->whereIn('payable_id', $payableIds);

        $grossMinor = (int) (clone $transactions)->sum('amount_minor');
        $refundedMinor = (int) PaymentRefundRecord::query()
            ->whereIn('payment_transaction_id', (clone $transactions)->pluck('id'))
            ->sum('amount_minor');

        return $grossMinor - $refundedMinor;
    }

    /**
     * Partial refunds are deliberately NOT netted out of the daily trend
     * here — a full refund already drops that day's figure retroactively
     * today, this is the same accepted approximation, not a regression.
     */
    public function dailyConfirmedRevenue(string $payableType, array $payableIds, DateTimeInterface $since): array
    {
        return PaymentTransactionRecord::query()
            ->where('payable_type', $payableType)
            ->where('status', 'confirmed')
            ->where('created_at', '>=', $since)
            ->whereIn('payable_id', $payableIds)
            ->selectRaw('DATE(created_at) as day, SUM(amount_minor) as revenue_minor')
            ->groupBy('day')
            ->pluck('revenue_minor', 'day')
            ->map(fn ($minor): int => (int) $minor)
            ->all();
    }
}
