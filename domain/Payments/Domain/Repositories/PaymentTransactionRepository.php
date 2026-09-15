<?php

declare(strict_types=1);

namespace Domain\Payments\Domain\Repositories;

use Domain\Payments\Domain\Entities\PaymentTransaction;
use Domain\Payments\Domain\ValueObjects\PayableType;
use Domain\Payments\Domain\ValueObjects\PaymentTransactionId;

interface PaymentTransactionRepository
{
    public function get(PaymentTransactionId $id): PaymentTransaction;

    /** Locks the transaction row with SELECT ... FOR UPDATE — the serialization point for a duplicate confirm/dispute/refund race. */
    public function lockForUpdate(PaymentTransactionId $id): PaymentTransaction;

    public function save(PaymentTransaction $transaction): PaymentTransactionId;

    /** DBDD §16.1: "find payments belonging to my business object" without a foreign key. */
    public function findByPayable(PayableType $payableType, int $payableId): array;
}
