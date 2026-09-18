<?php

declare(strict_types=1);

namespace Domain\Payments\Infrastructure\Laravel;

use Domain\Payments\Application\Contracts\PaymentTransactionLookup;
use Domain\Payments\Domain\Entities\PaymentTransaction;
use Domain\Payments\Domain\Repositories\PaymentTransactionRepository;
use Domain\Payments\Domain\ValueObjects\PayableType;

/** Reads through the repository's existing `findByPayable` (DBDD §16.1) rather than adding a new query path. */
final class HandlerBackedPaymentTransactionLookup implements PaymentTransactionLookup
{
    public function __construct(private readonly PaymentTransactionRepository $transactions) {}

    public function pendingTransactionFor(string $payableType, int $payableId): ?array
    {
        $transactions = $this->transactions->findByPayable(new PayableType($payableType), $payableId);

        $pending = array_values(array_filter(
            $transactions,
            static fn (PaymentTransaction $transaction): bool => in_array($transaction->status(), ['pending', 'payment_pending_confirmation'], true),
        ));

        if ($pending === []) {
            return null;
        }

        usort($pending, static fn (PaymentTransaction $a, PaymentTransaction $b): int => ($b->id()?->value ?? 0) <=> ($a->id()?->value ?? 0));

        $latest = $pending[0];

        return [
            'id' => $latest->id()?->value ?? 0,
            'status' => $latest->status(),
            'method' => $latest->method()->value,
            'amount_minor' => $latest->amount()->minor,
            'provider_reference' => $latest->providerReference(),
        ];
    }
}
