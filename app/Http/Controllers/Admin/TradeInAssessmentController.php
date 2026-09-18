<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\FlashesToast;
use App\Http\Requests\Admin\Warranty\ApplyTradeInCreditRequest;
use App\Http\Requests\Admin\Warranty\AssessTradeInRequest;
use App\Http\Requests\Admin\Warranty\SubmitTradeInRequest;
use Domain\Identity\Application\Contracts\CustomerDirectoryQuery;
use Domain\Shared\Application\DTOs\ActorContext;
use Domain\Warranty\Application\Commands\ApplyTradeInCreditCommand;
use Domain\Warranty\Application\Commands\ApproveTradeInCommand;
use Domain\Warranty\Application\Commands\AssessTradeInCommand;
use Domain\Warranty\Application\Commands\RejectTradeInCommand;
use Domain\Warranty\Application\Commands\SubmitTradeInAssessmentCommand;
use Domain\Warranty\Application\Handlers\ApplyTradeInCreditHandler;
use Domain\Warranty\Application\Handlers\ApproveTradeInHandler;
use Domain\Warranty\Application\Handlers\AssessTradeInHandler;
use Domain\Warranty\Application\Handlers\RejectTradeInHandler;
use Domain\Warranty\Application\Handlers\SubmitTradeInAssessmentHandler;
use Domain\Warranty\Application\Queries\TradeInAssessmentQueueQuery;
use Domain\Warranty\Domain\Exceptions\InvalidTradeInAssessmentTransition;
use Domain\Warranty\Domain\Exceptions\TradeInApproverMustDifferFromAssessor;
use Domain\Warranty\Domain\Exceptions\TradeInCreditCouldNotBeApplied;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/** Staff-initiated only — no customer routes (ADD's own directory tree lists no Customer TradeIns page). */
final class TradeInAssessmentController
{
    use FlashesToast;

    public function __construct(
        private readonly TradeInAssessmentQueueQuery $queue,
        private readonly CustomerDirectoryQuery $customers,
        private readonly SubmitTradeInAssessmentHandler $submitTradeIn,
        private readonly AssessTradeInHandler $assessTradeIn,
        private readonly ApproveTradeInHandler $approveTradeIn,
        private readonly RejectTradeInHandler $rejectTradeIn,
        private readonly ApplyTradeInCreditHandler $applyCredit,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Warranty/TradeIns/Index', [
            'tradeIns' => $this->queue->paginate(
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

    public function create(Request $request): Response
    {
        return Inertia::render('Admin/Warranty/TradeIns/Create', [
            'checkoutId' => $request->integer('checkout') ?: null,
        ]);
    }

    public function store(SubmitTradeInRequest $request): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $id = $this->submitTradeIn->handle(new SubmitTradeInAssessmentCommand(
            customerId: $request->integer('customer_id'),
            relatedCheckoutId: $request->integer('related_checkout_id') ?: null,
            deviceDescription: [
                'make' => $request->string('device_make')->toString(),
                'model' => $request->string('device_model')->toString(),
                'imei' => $request->string('device_imei')->toString() ?: null,
                'condition' => $request->string('device_condition')->toString(),
            ],
            submittedByStaffId: $actor->staffId->value,
        ));

        $this->flashSuccess('Trade-in recorded');

        return redirect()->route('admin.warranty.trade-ins.show', ['tradeIn' => $id]);
    }

    public function show(int $tradeIn): Response
    {
        $data = $this->queue->find($tradeIn);

        abort_if($data === null, 404);

        return Inertia::render('Admin/Warranty/TradeIns/Show', ['tradeIn' => $data]);
    }

    public function assess(AssessTradeInRequest $request, int $tradeIn): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->guardTransition(fn () => $this->assessTradeIn->handle(new AssessTradeInCommand(
            tradeInAssessmentId: $tradeIn,
            assessedValueMinor: $request->integer('assessed_value_minor'),
            assessedByStaffId: $actor->staffId->value,
        )));

        $this->flashSuccess('Trade-in assessed');

        return redirect()->route('admin.warranty.trade-ins.show', ['tradeIn' => $tradeIn]);
    }

    public function approve(int $tradeIn): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->guardTransition(fn () => $this->approveTradeIn->handle(new ApproveTradeInCommand(
            tradeInAssessmentId: $tradeIn,
            approvedByStaffId: $actor->staffId->value,
        )));

        $this->flashSuccess('Trade-in approved');

        return redirect()->route('admin.warranty.trade-ins.show', ['tradeIn' => $tradeIn]);
    }

    public function reject(int $tradeIn): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->guardTransition(fn () => $this->rejectTradeIn->handle(new RejectTradeInCommand(
            tradeInAssessmentId: $tradeIn,
            rejectedByStaffId: $actor->staffId->value,
        )));

        $this->flashWarning('Trade-in rejected');

        return redirect()->route('admin.warranty.trade-ins.show', ['tradeIn' => $tradeIn]);
    }

    public function applyCredit(ApplyTradeInCreditRequest $request, int $tradeIn): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->guardTransition(fn () => $this->applyCredit->handle(new ApplyTradeInCreditCommand(
            tradeInAssessmentId: $tradeIn,
            checkoutId: $request->integer('checkout_id'),
            appliedByStaffId: $actor->staffId->value,
        )));

        $this->flashSuccess('Trade-in credit applied');

        return redirect()->route('admin.warranty.trade-ins.show', ['tradeIn' => $tradeIn]);
    }

    public function searchCustomers(Request $request): JsonResponse
    {
        $term = $request->string('q')->toString();

        return response()->json([
            'results' => $term !== '' ? $this->customers->search($term) : [],
        ]);
    }

    /** An illegal transition or the TRADE-BR-05 same-staff check surfaces as a friendly validation message, not a 500. */
    private function guardTransition(callable $action): mixed
    {
        try {
            return $action();
        } catch (InvalidTradeInAssessmentTransition|TradeInApproverMustDifferFromAssessor|TradeInCreditCouldNotBeApplied $exception) {
            throw ValidationException::withMessages(['status' => $exception->getMessage()]);
        }
    }
}
