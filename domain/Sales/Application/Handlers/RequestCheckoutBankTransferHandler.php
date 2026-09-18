<?php

declare(strict_types=1);

namespace Domain\Sales\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Payments\Application\Commands\InitiatePaymentCommand;
use Domain\Payments\Application\Contracts\PaymentInitiationService;
use Domain\Sales\Application\Commands\RequestCheckoutBankTransferCommand;
use Domain\Sales\Domain\Exceptions\CheckoutDoesNotBelongToCustomer;
use Domain\Sales\Domain\Exceptions\CheckoutNotOpen;
use Domain\Sales\Domain\Repositories\SalesCheckoutRepository;
use Domain\Sales\Domain\ValueObjects\CheckoutId;
use Domain\Shared\Domain\ValueObjects\CustomerId;

/**
 * Creates a `payment_pending_confirmation` PaymentTransaction for a
 * customer's own open checkout — never marks the checkout paid, never
 * creates a Sale. Staff completes the sale afterward via the existing
 * `CreateSaleFromPaidCheckoutHandler`, which confirms and reuses this
 * transaction rather than creating a duplicate.
 */
final class RequestCheckoutBankTransferHandler
{
    public function __construct(
        private readonly SalesCheckoutRepository $checkouts,
        private readonly PaymentInitiationService $payments,
        private readonly Atomic $atomic,
    ) {}

    public function handle(RequestCheckoutBankTransferCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $checkoutId = new CheckoutId($command->checkoutId);
            $checkout = $this->checkouts->lockForUpdate($checkoutId);

            if (! $checkout->customerId()?->equals(new CustomerId($command->customerId))) {
                throw CheckoutDoesNotBelongToCustomer::forCheckout($command->checkoutId);
            }

            if ($checkout->status() !== 'open') {
                throw CheckoutNotOpen::forCheckout($command->checkoutId, $checkout->status());
            }

            $this->payments->initiate(new InitiatePaymentCommand(
                payableType: 'sales_checkout',
                payableId: $command->checkoutId,
                method: 'bank_transfer',
                amountMinor: $checkout->totalMinor(),
                initiatedByCustomerId: $command->customerId,
                providerReference: $command->transferReference,
            ));
        });
    }
}
