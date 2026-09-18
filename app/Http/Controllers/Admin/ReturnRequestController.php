<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\FlashesToast;
use App\Http\Requests\Admin\Warranty\DenyReturnRequestRequest;
use App\Http\Requests\Admin\Warranty\ProcessReturnRefundRequest;
use Domain\Shared\Application\DTOs\ActorContext;
use Domain\Warranty\Application\Commands\ApproveReturnRequestCommand;
use Domain\Warranty\Application\Commands\AssessReturnRequestCommand;
use Domain\Warranty\Application\Commands\DenyReturnRequestCommand;
use Domain\Warranty\Application\Commands\OverrideApproveReturnRequestCommand;
use Domain\Warranty\Application\Commands\ProcessReturnRefundCommand;
use Domain\Warranty\Application\Handlers\ApproveReturnRequestHandler;
use Domain\Warranty\Application\Handlers\AssessReturnRequestHandler;
use Domain\Warranty\Application\Handlers\DenyReturnRequestHandler;
use Domain\Warranty\Application\Handlers\OverrideApproveReturnRequestHandler;
use Domain\Warranty\Application\Handlers\ProcessReturnRefundHandler;
use Domain\Warranty\Application\Queries\ReturnRequestDetailQuery;
use Domain\Warranty\Application\Queries\ReturnRequestQueueQuery;
use Domain\Warranty\Domain\Exceptions\InvalidReturnRequestTransition;
use Domain\Warranty\Domain\Exceptions\ReturnWindowExpired;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class ReturnRequestController
{
    use FlashesToast;

    public function __construct(
        private readonly ReturnRequestQueueQuery $queue,
        private readonly ReturnRequestDetailQuery $detail,
        private readonly AssessReturnRequestHandler $assess,
        private readonly ApproveReturnRequestHandler $approve,
        private readonly DenyReturnRequestHandler $deny,
        private readonly OverrideApproveReturnRequestHandler $overrideApprove,
        private readonly ProcessReturnRefundHandler $processRefund,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Warranty/Returns/Index', [
            'returns' => $this->queue->paginate(
                search: $request->string('search')->toString() ?: null,
                status: $request->string('status')->toString() ?: null,
                page: $request->integer('page', 1),
            ),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'status' => $request->string('status')->toString(),
            ],
        ]);
    }

    public function show(int $returnRequest): Response
    {
        $data = $this->detail->find($returnRequest);

        abort_if($data === null, 404);

        return Inertia::render('Admin/Warranty/Returns/Show', ['returnRequest' => $data]);
    }

    public function assess(int $returnRequest): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->guardTransition(fn () => $this->assess->handle(new AssessReturnRequestCommand(
            returnRequestId: $returnRequest,
            assessedByStaffId: $actor->staffId->value,
        )));

        $this->flashSuccess('Return under assessment');

        return redirect()->route('admin.warranty.returns.show', ['returnRequest' => $returnRequest]);
    }

    public function approve(int $returnRequest): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->guardTransition(fn () => $this->approve->handle(new ApproveReturnRequestCommand(
            returnRequestId: $returnRequest,
            approvedByStaffId: $actor->staffId->value,
        )));

        $this->flashSuccess('Return approved');

        return redirect()->route('admin.warranty.returns.show', ['returnRequest' => $returnRequest]);
    }

    public function deny(DenyReturnRequestRequest $request, int $returnRequest): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->guardTransition(fn () => $this->deny->handle(new DenyReturnRequestCommand(
            returnRequestId: $returnRequest,
            deniedByStaffId: $actor->staffId->value,
            reason: $request->string('reason')->toString(),
        )));

        $this->flashWarning('Return denied');

        return redirect()->route('admin.warranty.returns.show', ['returnRequest' => $returnRequest]);
    }

    public function overrideApprove(int $returnRequest): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->guardTransition(fn () => $this->overrideApprove->handle(new OverrideApproveReturnRequestCommand(
            returnRequestId: $returnRequest,
            overriddenByStaffId: $actor->staffId->value,
        )));

        $this->flashSuccess('Return approved by override');

        return redirect()->route('admin.warranty.returns.show', ['returnRequest' => $returnRequest]);
    }

    public function processRefund(ProcessReturnRefundRequest $request, int $returnRequest): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->guardTransition(fn () => $this->processRefund->handle(new ProcessReturnRefundCommand(
            returnRequestId: $returnRequest,
            paymentTransactionId: $request->integer('payment_transaction_id'),
            processedByStaffId: $actor->staffId->value,
        )));

        $this->flashSuccess('Refund processed');

        return redirect()->route('admin.warranty.returns.show', ['returnRequest' => $returnRequest]);
    }

    /** An illegal transition or expired return window surfaces as a friendly validation message, not a 500 — mirrors WarrantyClaimController::guardTransition. */
    private function guardTransition(callable $action): mixed
    {
        try {
            return $action();
        } catch (InvalidReturnRequestTransition|ReturnWindowExpired $exception) {
            throw ValidationException::withMessages(['status' => $exception->getMessage()]);
        }
    }
}
