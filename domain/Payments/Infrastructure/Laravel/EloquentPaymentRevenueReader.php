<?php

declare(strict_types=1);

namespace Domain\Payments\Infrastructure\Laravel;

use DateTimeInterface;
use Domain\Payments\Application\Contracts\PaymentRevenueReader;
use Domain\Payments\Infrastructure\Persistence\Eloquent\PaymentTransactionRecord;

final class EloquentPaymentRevenueReader implements PaymentRevenueReader
{
    public function confirmedRevenueMinor(string $payableType, array $payableIds): int
    {
        return (int) PaymentTransactionRecord::query()
            ->where('payable_type', $payableType)
            ->where('status', 'confirmed')
            ->whereIn('payable_id', $payableIds)
            ->sum('amount_minor');
    }

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
