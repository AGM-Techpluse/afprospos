<?php

declare(strict_types=1);

namespace Domain\Payments\Application\DTOs;

/** What the caller (Sales, and later Repair) needs back — the transaction's ID, so it can be stored as the payable's own `payment_transaction_id`. */
final readonly class PaymentInitiationResult
{
    public function __construct(
        public int $transactionId,
        public string $status,
        public ?string $providerReference,
    ) {}
}
