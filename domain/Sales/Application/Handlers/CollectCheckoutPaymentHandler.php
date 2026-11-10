<?php

declare(strict_types=1);

namespace Domain\Sales\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Payments\Application\Commands\ConfirmPaymentCommand;
use Domain\Payments\Application\Commands\InitiatePaymentCommand;
use Domain\Payments\Application\Contracts\PaymentConfirmationService;
use Domain\Payments\Application\Contracts\PaymentInitiationService;
use Domain\Payments\Application\Contracts\PaymentTransactionLookup;
use Domain\Sales\Application\Commands\CollectCheckoutPaymentCommand;
use Domain\Sales\Application\Commands\CreateSaleFromPaidCheckoutCommand;
use Domain\Sales\Domain\Exceptions\CheckoutNotOpen;
use Domain\Sales\Domain\Exceptions\SplitPaymentLegNotImmediatelyConfirmable;
use Domain\Sales\Domain\Repositories\SalesCheckoutRepository;
use Domain\Sales\Domain\ValueObjects\CheckoutId;

/**
 * Covers split payment (2+ legs submitted together, reaching the full
 * total at once) and part payment (1 leg for less than the total, the
 * checkout staying open for a later submission) with one mechanism: every
 * leg becomes its own confirmed `payments_transactions` row, and once the
 * running total — this submission plus anything already confirmed earlier
 * for this checkout — reaches the checkout's total, the sale is finalized
 * by delegating to the existing, unchanged CreateSaleFromPaidCheckoutHandler
 * (via its `existingPaymentTransactionId` path, the same one a
 * customer-submitted bank transfer already uses) rather than duplicating
 * its completion logic.
 */
final class CollectCheckoutPaymentHandler
{
    public function __construct(
        private readonly SalesCheckoutRepository $checkouts,
        private readonly PaymentInitiationService $payments,
        private readonly PaymentConfirmationService $confirmations,
        private readonly PaymentTransactionLookup $paymentLookup,
        private readonly CreateSaleFromPaidCheckoutHandler $completeSale,
        private readonly Atomic $atomic,
    ) {}

    /** @return array{sale_id: ?int, paid_minor: int, remaining_minor: int} */
    public function handle(CollectCheckoutPaymentCommand $command): array
    {
        return $this->atomic->run(function () use ($command): array {
            $checkoutId = new CheckoutId($command->checkoutId);
            $checkout = $this->checkouts->get($checkoutId);

            if ($checkout->status() !== 'open') {
                throw CheckoutNotOpen::forCheckout($command->checkoutId, $checkout->status());
            }

            $hasBankTransferLeg = in_array('bank_transfer', array_column($command->payments, 'method'), true);

            if (count($command->payments) > 1 && $hasBankTransferLeg) {
                throw SplitPaymentLegNotImmediatelyConfirmable::forMethod('bank_transfer');
            }

            $priorConfirmed = $this->paymentLookup->confirmedTransactionsFor('sales_checkout', $command->checkoutId, 'sale_payment');
            $contributingMethods = array_map(static fn (array $row): string => $row['method'], $priorConfirmed);
            $paidMinor = array_sum(array_column($priorConfirmed, 'amount_minor'));
            $lastConfirmedTransactionId = $priorConfirmed === [] ? null : end($priorConfirmed)['id'];

            if ($command->existingPaymentTransactionId !== null) {
                $this->confirmations->confirm(new ConfirmPaymentCommand(
                    transactionId: $command->existingPaymentTransactionId,
                    confirmedByStaffId: $command->confirmedByStaffId,
                    providerReference: null,
                ));

                $lastConfirmedTransactionId = $command->existingPaymentTransactionId;
                $contributingMethods[] = 'bank_transfer';
                $paidMinor += $this->amountOf($command->existingPaymentTransactionId, $priorConfirmed, $checkout->totalMinor() - $paidMinor);
            }

            foreach ($command->payments as $leg) {
                $result = $this->payments->initiate(new InitiatePaymentCommand(
                    payableType: 'sales_checkout',
                    payableId: $command->checkoutId,
                    purpose: 'sale_payment',
                    method: $leg['method'],
                    amountMinor: $leg['amount_minor'],
                    initiatedByStaffId: $command->confirmedByStaffId,
                    providerReference: $leg['reference'] ?? null,
                ));

                if ($result->status === 'confirmed') {
                    $lastConfirmedTransactionId = $result->transactionId;
                    $contributingMethods[] = $leg['method'];
                    $paidMinor += $leg['amount_minor'];
                }
                // A leg that comes back pending (bank_transfer, submitted alone) simply doesn't
                // count toward $paidMinor yet — it's confirmed later via the Payments page, same
                // as today's existing single-method bank-transfer flow.
            }

            if ($paidMinor < $checkout->totalMinor() || $lastConfirmedTransactionId === null) {
                return ['sale_id' => null, 'paid_minor' => $paidMinor, 'remaining_minor' => max(0, $checkout->totalMinor() - $paidMinor)];
            }

            $saleId = $this->completeSale->handle(new CreateSaleFromPaidCheckoutCommand(
                checkoutId: $command->checkoutId,
                paymentMethod: count(array_unique($contributingMethods)) > 1 ? 'split' : ($contributingMethods[0] ?? 'cash'),
                paymentReference: null,
                confirmedByStaffId: $command->confirmedByStaffId,
                shopCode: $command->shopCode,
                existingPaymentTransactionId: $lastConfirmedTransactionId,
            ));

            return ['sale_id' => $saleId, 'paid_minor' => $paidMinor, 'remaining_minor' => 0];
        });
    }

    /** @param  array<int, array{id: int, method: string, amount_minor: int}>  $priorConfirmed */
    private function amountOf(int $transactionId, array $priorConfirmed, int $fallback): int
    {
        foreach ($priorConfirmed as $row) {
            if ($row['id'] === $transactionId) {
                return $row['amount_minor'];
            }
        }

        // Not found among prior-confirmed (it was only just confirmed above) — the remaining
        // balance is exactly what a customer-submitted bank transfer is expected to cover.
        return $fallback;
    }
}
