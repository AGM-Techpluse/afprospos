<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\FlashesToast;
use App\Http\Requests\Admin\Warranty\ApproveRefundRequest;
use App\Http\Requests\Admin\Warranty\AssessClaimRequest;
use App\Http\Requests\Admin\Warranty\CreateWarrantyClaimRequest;
use App\Http\Requests\Admin\Warranty\ResolveClaimRequest;
use App\Http\Requests\Admin\Warranty\SelectWarrantyClaimRemedyRequest;
use Domain\Identity\Application\Contracts\CustomerDirectoryQuery;
use Domain\Repair\Application\Contracts\RepairJobLookup;
use Domain\Sales\Application\Contracts\SaleLookup;
use Domain\Shared\Application\DTOs\ActorContext;
use Domain\Warranty\Application\Commands\ApproveRefundCommand;
use Domain\Warranty\Application\Commands\AssessClaimCommand;
use Domain\Warranty\Application\Commands\CreateWarrantyClaimCommand;
use Domain\Warranty\Application\Commands\ResolveClaimCommand;
use Domain\Warranty\Application\Commands\SelectWarrantyClaimRemedyCommand;
use Domain\Warranty\Application\Handlers\ApproveRefundHandler;
use Domain\Warranty\Application\Handlers\AssessClaimHandler;
use Domain\Warranty\Application\Handlers\CreateWarrantyClaimHandler;
use Domain\Warranty\Application\Handlers\ResolveClaimHandler;
use Domain\Warranty\Application\Handlers\SelectWarrantyClaimRemedyHandler;
use Domain\Warranty\Application\Queries\WarrantyClaimDetailQuery;
use Domain\Warranty\Application\Queries\WarrantyClaimQueueQuery;
use Domain\Warranty\Application\Queries\WarrantyPolicyDirectoryQuery;
use Domain\Warranty\Domain\Exceptions\InvalidWarrantyClaimSource;
use Domain\Warranty\Domain\Exceptions\InvalidWarrantyClaimTransition;
use Domain\Warranty\Domain\Exceptions\UnsupportedWarrantyRemedy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class WarrantyClaimController
{
    use FlashesToast;

    public function __construct(
        private readonly WarrantyClaimQueueQuery $queue,
        private readonly WarrantyClaimDetailQuery $detail,
        private readonly WarrantyPolicyDirectoryQuery $policies,
        private readonly CustomerDirectoryQuery $customers,
        private readonly SaleLookup $saleLookup,
        private readonly RepairJobLookup $repairJobLookup,
        private readonly CreateWarrantyClaimHandler $createClaim,
        private readonly AssessClaimHandler $assessClaim,
        private readonly SelectWarrantyClaimRemedyHandler $selectRemedy,
        private readonly ResolveClaimHandler $resolveClaim,
        private readonly ApproveRefundHandler $approveRefund,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Warranty/Claims/Index', [
            'claims' => $this->queue->paginate(
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

    public function create(): Response
    {
        return Inertia::render('Admin/Warranty/Claims/Create', [
            'policies' => $this->policies->all(),
        ]);
    }

    public function store(CreateWarrantyClaimRequest $request): RedirectResponse
    {
        $actor = app(ActorContext::class);
        $originatingSaleId = $request->integer('originating_sale_id') ?: null;
        $originatingRepairJobId = $request->integer('originating_repair_job_id') ?: null;
        $this->assertOriginShopAccessible($actor, $originatingSaleId, $originatingRepairJobId);

        $id = $this->guardTransition(fn () => $this->createClaim->handle(new CreateWarrantyClaimCommand(
            warrantyPolicyId: $request->integer('warranty_policy_id'),
            originatingSaleId: $originatingSaleId,
            originatingRepairJobId: $originatingRepairJobId,
            customerId: $request->integer('customer_id'),
            submittedByStaffId: $actor->staffId->value,
        )));

        $this->flashSuccess('Warranty claim submitted');

        return redirect()->route('admin.warranty.claims.show', ['claim' => $id]);
    }

    public function show(int $claim): Response
    {
        $actor = app(ActorContext::class);
        $data = $this->detail->find($claim);

        abort_if($data === null, 404);
        $this->assertClaimDataShopAccessible($actor, $data);

        return Inertia::render('Admin/Warranty/Claims/Show', ['claim' => $data]);
    }

    public function assess(AssessClaimRequest $request, int $claim): RedirectResponse
    {
        $actor = app(ActorContext::class);
        $this->assertClaimShopAccess($actor, $claim);

        $this->guardTransition(fn () => $this->assessClaim->handle(new AssessClaimCommand(
            warrantyClaimId: $claim,
            eligible: $request->boolean('eligible'),
            notes: $request->string('notes')->toString() ?: null,
            assessedByStaffId: $actor->staffId->value,
        )));

        $this->flashSuccess('Claim assessed');

        return redirect()->route('admin.warranty.claims.show', ['claim' => $claim]);
    }

    public function selectRemedy(SelectWarrantyClaimRemedyRequest $request, int $claim): RedirectResponse
    {
        $actor = app(ActorContext::class);
        $this->assertClaimShopAccess($actor, $claim);

        $this->guardTransition(fn () => $this->selectRemedy->handle(new SelectWarrantyClaimRemedyCommand(
            warrantyClaimId: $claim,
            remedy: $request->string('remedy')->toString(),
            selectedByStaffId: $actor->staffId->value,
        )));

        $this->flashSuccess('Remedy selected');

        return redirect()->route('admin.warranty.claims.show', ['claim' => $claim]);
    }

    public function resolve(ResolveClaimRequest $request, int $claim): RedirectResponse
    {
        $actor = app(ActorContext::class);
        $this->assertClaimShopAccess($actor, $claim);

        $this->guardTransition(fn () => $this->resolveClaim->handle(new ResolveClaimCommand(
            warrantyClaimId: $claim,
            remedyReferenceId: $request->integer('remedy_reference_id'),
            resolvedByStaffId: $actor->staffId->value,
        )));

        $this->flashSuccess('Claim resolved');

        return redirect()->route('admin.warranty.claims.show', ['claim' => $claim]);
    }

    public function approveRefund(ApproveRefundRequest $request, int $claim): RedirectResponse
    {
        $actor = app(ActorContext::class);
        $this->assertClaimShopAccess($actor, $claim);

        $this->guardTransition(fn () => $this->approveRefund->handle(new ApproveRefundCommand(
            warrantyClaimId: $claim,
            paymentTransactionId: $request->integer('payment_transaction_id'),
            approvedByStaffId: $actor->staffId->value,
        )));

        $this->flashSuccess('Refund approved');

        return redirect()->route('admin.warranty.claims.show', ['claim' => $claim]);
    }

    public function searchCustomers(Request $request): JsonResponse
    {
        $term = $request->string('q')->toString();

        return response()->json([
            'results' => $term !== '' ? $this->customers->search($term) : [],
        ]);
    }

    /** BRD RBAC-05: every action on one specific claim is blocked unless the acting staff member (or the Shop Owner) was granted the originating sale/repair's shop. */
    private function assertClaimShopAccess(ActorContext $actor, int $claimId): void
    {
        $data = $this->detail->find($claimId);
        abort_if($data === null, 404);
        $this->assertClaimDataShopAccessible($actor, $data);
    }

    /** @param  array<string, mixed>  $data */
    private function assertClaimDataShopAccessible(ActorContext $actor, array $data): void
    {
        $shopId = $data['originating_sale']['shop_id'] ?? $data['originating_repair_job']['shop_id'] ?? null;

        if ($shopId !== null) {
            abort_unless($actor->canAccessShop((int) $shopId), 403);
        }
    }

    private function assertOriginShopAccessible(ActorContext $actor, ?int $originatingSaleId, ?int $originatingRepairJobId): void
    {
        $shopId = $originatingSaleId !== null
            ? $this->saleLookup->find($originatingSaleId)['shop_id'] ?? null
            : ($originatingRepairJobId !== null ? $this->repairJobLookup->find($originatingRepairJobId)['shop_id'] ?? null : null);

        if ($shopId !== null) {
            abort_unless($actor->canAccessShop((int) $shopId), 403);
        }
    }

    /** An illegal transition, unsupported remedy, or invalid source reference surfaces as a friendly validation message, not a 500 — mirrors RepairsController::guardTransition. */
    private function guardTransition(callable $action): mixed
    {
        try {
            return $action();
        } catch (InvalidWarrantyClaimTransition|InvalidWarrantyClaimSource|UnsupportedWarrantyRemedy $exception) {
            throw ValidationException::withMessages(['status' => $exception->getMessage()]);
        }
    }
}
