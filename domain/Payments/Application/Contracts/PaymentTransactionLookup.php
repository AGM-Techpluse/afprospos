<?php

declare(strict_types=1);

namespace Domain\Payments\Application\Contracts;

/**
 * Published cross-module read contract (mirrors `PaymentInitiationService`'s
 * role): lets another module check "is there already a payment in flight
 * for my payable" without depending on Payments' Domain/Infrastructure
 * internals or Eloquent models directly (CPNC §4.2).
 */
interface PaymentTransactionLookup
{
    /**
     * The most recent not-yet-settled transaction for a payable, or null.
     * "Pending" here means `pending`/`payment_pending_confirmation` — the
     * states a caller still needs to reconcile against, not a terminal one.
     *
     * @return array{id: int, status: string, method: string, amount_minor: int, provider_reference: ?string}|null
     */
    public function pendingTransactionFor(string $payableType, int $payableId): ?array;
}
