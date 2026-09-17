<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\FlashesToast;
use App\Http\Requests\Admin\ConfirmPaymentRequest;
use App\Http\Requests\Admin\OpenPaymentDisputeRequest;
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
use Domain\Shared\Application\DTOs\ActorContext;
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
            fputcsv($handle, ['ID', 'Payable type', 'Payable ID', 'Method', 'Amount (NGN)', 'Status', 'Provider reference', 'Created at']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['id'],
                    $row['payable_type'],
                    $row['payable_id'],
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

    public function confirm(ConfirmPaymentRequest $request, int $payment): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->guardTransition(fn () => $this->confirmPayment->handle(new ConfirmPaymentCommand(
            transactionId: $payment,
            confirmedByStaffId: $actor->staffId->value,
            providerReference: $request->string('provider_reference')->toString() ?: null,
        )));

        $this->flashSuccess('Payment confirmed');

        return redirect()->route('admin.payments.show', ['payment' => $payment]);
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
        )));

        $this->flashSuccess('Dispute resolved');

        return redirect()->route('admin.payments.show', ['payment' => $payment]);
    }

    public function refund(int $payment): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->guardTransition(fn () => $this->refundPayment->handle(new RefundPaymentCommand(
            transactionId: $payment,
            refundedByStaffId: $actor->staffId->value,
        )));

        $this->flashSuccess('Payment refunded');

        return redirect()->route('admin.payments.show', ['payment' => $payment]);
    }

    /** An illegal transition (e.g. confirming an already-refunded payment) surfaces as a friendly validation message, not a 500. */
    private function guardTransition(callable $action): void
    {
        try {
            $action();
        } catch (InvalidPaymentStateTransition $exception) {
            throw ValidationException::withMessages(['status' => $exception->getMessage()]);
        }
    }
}
