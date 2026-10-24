<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\FlashesToast;
use App\Http\Requests\Admin\ConfirmPaymentRequest;
use App\Http\Requests\Admin\OpenPaymentDisputeRequest;
use App\Http\Requests\Admin\RefundPaymentRequest;
use App\Http\Requests\Admin\ResolvePaymentDisputeRequest;
use Domain\Payments\Application\Commands\ConfirmPaymentCommand;
use Domain\Payments\Application\Commands\OpenPaymentDisputeCommand;
use Domain\Payments\Application\Commands\RefundPaymentCommand;
use Domain\Payments\Application\Commands\RejectPaymentCommand;
use Domain\Payments\Application\Commands\ResolvePaymentDisputeCommand;
use Domain\Payments\Application\Handlers\ConfirmPaymentHandler;
use Domain\Payments\Application\Handlers\OpenPaymentDisputeHandler;
use Domain\Payments\Application\Handlers\RefundPaymentHandler;
use Domain\Payments\Application\Handlers\RejectPaymentHandler;
use Domain\Payments\Application\Handlers\ResolvePaymentDisputeHandler;
use Domain\Payments\Application\Queries\PaymentTransactionDetailQuery;
use Domain\Payments\Application\Queries\PaymentTransactionsQuery;
use Domain\Payments\Domain\Exceptions\InvalidPaymentStateTransition;
use Domain\Payments\Domain\Exceptions\PaymentRefundExceedsRemainingBalance;
use Domain\Sales\Application\Commands\CreateSaleFromPaidCheckoutCommand;
use Domain\Sales\Application\Handlers\CreateSaleFromPaidCheckoutHandler;
use Domain\Sales\Application\Queries\CheckoutDetailQuery;
use Domain\Sales\Domain\Exceptions\CheckoutNotOpen;
use Domain\Sales\Domain\Exceptions\CheckoutReservationExpired;
use Domain\Sales\Domain\Exceptions\DiscountNotApproved;
use Domain\Shared\Application\DTOs\ActorContext;
use Domain\Shop\Application\Queries\ShopDirectoryQuery;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PaymentController
{
    use FlashesToast;

    public function __construct(
        private readonly PaymentTransactionsQuery $list,
        private readonly PaymentTransactionDetailQuery $detail,
        private readonly ConfirmPaymentHandler $confirmPayment,
        private readonly RejectPaymentHandler $rejectPayment,
        private readonly OpenPaymentDisputeHandler $openDispute,
        private readonly ResolvePaymentDisputeHandler $resolveDispute,
        private readonly RefundPaymentHandler $refundPayment,
        private readonly CheckoutDetailQuery $checkoutDetail,
        private readonly CreateSaleFromPaidCheckoutHandler $completeSale,
        private readonly ShopDirectoryQuery $shops,
    ) {}

    public function index(Request $request): Response
    {
        $status = $request->string('status')->toString() ?: null;

        return Inertia::render('Admin/Payments/Index', [
            'payments' => $this->list->paginate(
                status: $status,
                method: $request->string('method')->toString() ?: null,
                page: $request->integer('page', 1),
            ),
            'filters' => [
                'status' => $status,
                'method' => $request->string('method')->toString(),
            ],
        ]);
    }

    /**
     * Two modes: `ids[]` given exports exactly those transactions
     * ("export selected"); no `ids[]` exports everything matching the
     * current `status`/`method` filters ("export matching filter") —
     * both are offered on the Index page's toolbar, not one-or-the-other.
     */
    public function export(Request $request): StreamedResponse
    {
        $ids = $request->has('ids') ? array_map('intval', $request->array('ids')) : null;

        $rows = $this->list->exportRows(
            status: $ids === null ? ($request->string('status')->toString() ?: null) : null,
            method: $ids === null ? ($request->string('method')->toString() ?: null) : null,
            ids: $ids,
        );

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, ['ID', 'Purpose', 'For', 'Method', 'Amount (NGN)', 'Status', 'Provider reference', 'Created at']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['id'],
                    $row['purpose'],
                    $row['source_label'],
                    $row['method'],
                    number_format($row['amount_minor'] / 100, 2, '.', ''),
                    $row['status'],
                    $row['provider_reference'] ?? '',
                    $row['created_at'],
                ]);
            }

            fclose($handle);
        }, 'afprospos-payments-export.csv', ['Content-Type' => 'text/csv']);
    }

    public function show(int $payment): Response
    {
        $data = $this->detail->find($payment);

        abort_if($data === null, 404);

        return Inertia::render('Admin/Payments/Show', ['payment' => $data]);
    }

    /**
     * A self-service (storefront) checkout has no cashier who separately
     * "completes the sale" at a till the way a POS payment does — so
     * confirming a bank-transfer payment still tied to an open checkout
     * also finishes the order here, in one step, instead of leaving it
     * stuck "awaiting payment" until someone separately visits the
     * checkout screen to complete it.
     */
    public function confirm(ConfirmPaymentRequest $request, int $payment): RedirectResponse
    {
        $actor = app(ActorContext::class);
        $data = $this->detail->find($payment);
        abort_if($data === null, 404);

        $providerReference = $request->string('provider_reference')->toString() ?: null;
        $saleCompleted = null;

        if ($data['payable_type'] === 'sales_checkout' && $data['purpose'] === 'sale_payment') {
            $saleCompleted = $this->tryCompleteCheckoutSale($data, $payment, $providerReference, $actor->staffId->value);
        }

        if ($saleCompleted !== true) {
            $this->guardTransition(fn () => $this->confirmPayment->handle(new ConfirmPaymentCommand(
                transactionId: $payment,
                confirmedByStaffId: $actor->staffId->value,
                providerReference: $providerReference,
            )));
        }

        if ($saleCompleted === true) {
            $this->flashSuccess('Payment confirmed and the order completed');
        } elseif ($saleCompleted === false) {
            $this->flashWarning("Payment confirmed, but the order couldn't be completed automatically — finish it from the checkout.");
        } else {
            $this->flashSuccess('Payment confirmed');
        }

        return redirect()->route('admin.payments.show', ['payment' => $payment]);
    }

    /**
     * True once the sale is actually completed; false when the checkout
     * was open but completion still failed (e.g. its reservation already
     * expired — the caller falls back to a plain payment confirmation so
     * at least that much isn't lost); null when there's nothing to
     * complete here at all (already paid, or no such checkout) — a plain
     * confirmation is the whole story in that case.
     *
     * @param  array<string, mixed>  $paymentData
     */
    private function tryCompleteCheckoutSale(array $paymentData, int $paymentId, ?string $providerReference, int $staffId): ?bool
    {
        $checkoutData = $this->checkoutDetail->find((int) $paymentData['payable_id']);

        if ($checkoutData === null || $checkoutData['status'] !== 'open') {
            return null;
        }

        $shop = $this->shops->find((int) $checkoutData['shop_id']);

        if ($shop === null) {
            return null;
        }

        try {
            $this->completeSale->handle(new CreateSaleFromPaidCheckoutCommand(
                checkoutId: (int) $paymentData['payable_id'],
                paymentMethod: (string) $paymentData['method'],
                paymentReference: $providerReference,
                confirmedByStaffId: $staffId,
                shopCode: $shop['sku_prefix_code'],
                existingPaymentTransactionId: $paymentId,
            ));

            return true;
        } catch (CheckoutReservationExpired|CheckoutNotOpen|DiscountNotApproved) {
            return false;
        }
    }

    public function reject(int $payment): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->guardTransition(fn () => $this->rejectPayment->handle(new RejectPaymentCommand(
            transactionId: $payment,
            rejectedByStaffId: $actor->staffId->value,
        )));

        $this->flashWarning('Payment rejected');

        return redirect()->route('admin.payments.show', ['payment' => $payment]);
    }

    public function openDispute(OpenPaymentDisputeRequest $request, int $payment): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->guardTransition(fn () => $this->openDispute->handle(new OpenPaymentDisputeCommand(
            transactionId: $payment,
            openedByStaffId: $actor->staffId->value,
            disputeProofReference: $request->string('dispute_proof_reference')->toString() ?: null,
        )));

        $this->flashWarning('Dispute opened');

        return redirect()->route('admin.payments.show', ['payment' => $payment]);
    }

    public function resolveDispute(ResolvePaymentDisputeRequest $request, int $payment): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->guardTransition(fn () => $this->resolveDispute->handle(new ResolvePaymentDisputeCommand(
            transactionId: $payment,
            resolvedByStaffId: $actor->staffId->value,
            resolution: $request->string('resolution')->toString(),
            amountMinor: $request->filled('amount_minor') ? $request->integer('amount_minor') : null,
        )));

        $this->flashSuccess('Dispute resolved');

        return redirect()->route('admin.payments.show', ['payment' => $payment]);
    }

    public function refund(RefundPaymentRequest $request, int $payment): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->guardTransition(fn () => $this->refundPayment->handle(new RefundPaymentCommand(
            transactionId: $payment,
            refundedByStaffId: $actor->staffId->value,
            amountMinor: $request->filled('amount_minor') ? $request->integer('amount_minor') : null,
        )));

        $this->flashSuccess('Payment refunded');

        return redirect()->route('admin.payments.show', ['payment' => $payment]);
    }

    /** An illegal transition (e.g. confirming an already-refunded payment) or an over-amount refund surfaces as a friendly validation message, not a 500. */
    /** Mirrors RepairsController::guardTransition()'s fix — nothing on this controller's pages reads the `errors` prop, so a thrown ValidationException was a silent no-op. Flashing the error directly is what the toast system actually displays. */
    private function guardTransition(callable $action): void
    {
        try {
            $action();
        } catch (InvalidPaymentStateTransition $exception) {
            $this->flashError('Could not complete that action', $exception->getMessage());

            throw new HttpResponseException(redirect()->back());
        } catch (PaymentRefundExceedsRemainingBalance $exception) {
            $this->flashError('Could not complete that action', $exception->getMessage());

            throw new HttpResponseException(redirect()->back());
        }
    }
}
