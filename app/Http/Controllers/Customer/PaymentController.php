<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Concerns\FlashesToast;
use App\Http\Requests\Customer\RequestCheckoutBankTransferRequest;
use Domain\Payments\Application\Contracts\PaymentTransactionLookup;
use Domain\Sales\Application\Commands\RequestCheckoutBankTransferCommand;
use Domain\Sales\Application\Handlers\RequestCheckoutBankTransferHandler;
use Domain\Sales\Application\Queries\CheckoutDetailQuery;
use Domain\Sales\Domain\Exceptions\CheckoutDoesNotBelongToCustomer;
use Domain\Sales\Domain\Exceptions\CheckoutNotOpen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Customer-initiated bank-transfer payment (UI/UX §14C.7) — the only
 * method supported today (no online/card gateway is integrated yet).
 * Ownership is checked the same way OrdersController/RepairsController
 * already do: fetch by id, `abort_if` on `customer_id` mismatch.
 */
final class PaymentController
{
    use FlashesToast;

    public function __construct(
        private readonly CheckoutDetailQuery $checkoutDetail,
        private readonly PaymentTransactionLookup $paymentLookup,
        private readonly RequestCheckoutBankTransferHandler $requestBankTransfer,
    ) {}

    public function showCheckout(Request $request, int $checkout): Response
    {
        $data = $this->checkoutDetail->find($checkout);

        abort_if($data === null || $data['customer_id'] !== $request->user('customer')->id, 403);

        return Inertia::render('Customer/Payments/Show', [
            'checkout' => $data,
            'pendingTransaction' => $this->paymentLookup->pendingTransactionFor('sales_checkout', $checkout),
            'bankTransferInstructions' => config('afprospos.bank_transfer_instructions'),
        ]);
    }

    public function requestBankTransfer(RequestCheckoutBankTransferRequest $request, int $checkout): RedirectResponse
    {
        $data = $this->checkoutDetail->find($checkout);

        abort_if($data === null || $data['customer_id'] !== $request->user('customer')->id, 403);

        try {
            $this->requestBankTransfer->handle(new RequestCheckoutBankTransferCommand(
                checkoutId: $checkout,
                customerId: $request->user('customer')->id,
                transferReference: $request->string('transfer_reference')->toString() ?: null,
            ));
        } catch (CheckoutNotOpen|CheckoutDoesNotBelongToCustomer $exception) {
            throw ValidationException::withMessages(['transfer_reference' => $exception->getMessage()]);
        }

        $this->flashSuccess("Thanks — we'll confirm your transfer shortly.");

        return redirect()->route('customer.payments.checkouts.show', ['checkout' => $checkout]);
    }
}
