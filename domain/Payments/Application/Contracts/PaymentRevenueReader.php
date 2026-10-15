<?php

declare(strict_types=1);

namespace Domain\Payments\Application\Contracts;

use DateTimeInterface;

/** Confirmed-payment revenue by payable stream, for reporting. Payments owns the ledger; other modules only read the totals. */
interface PaymentRevenueReader
{
    /** @param  int[]  $payableIds */
    public function confirmedRevenueMinor(string $payableType, array $payableIds): int;

    /**
     * @param  int[]  $payableIds
     * @return array<string, int> day (Y-m-d) => confirmed revenue in minor units
     */
    public function dailyConfirmedRevenue(string $payableType, array $payableIds, DateTimeInterface $since): array;
}
