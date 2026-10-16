<?php

declare(strict_types=1);

namespace Domain\Payments\Domain\Repositories;

use Domain\Payments\Domain\Entities\PaymentRefund;
use Domain\Payments\Domain\ValueObjects\PaymentRefundId;
use Domain\Payments\Domain\ValueObjects\PaymentTransactionId;

/** The ledger is insert-only — there is no update/delete path, a correction is always a new entry. */
interface PaymentRefundRepository
{
    public function append(PaymentRefund $refund): PaymentRefundId;

    public function totalRefundedMinor(PaymentTransactionId $paymentTransactionId): int;

    /** @return PaymentRefund[] */
    public function listForTransaction(PaymentTransactionId $paymentTransactionId): array;
}
