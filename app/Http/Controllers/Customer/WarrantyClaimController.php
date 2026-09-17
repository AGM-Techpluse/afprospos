<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Concerns\FlashesToast;
use App\Http\Requests\Customer\Warranty\CreateWarrantyClaimRequest;
use Domain\Warranty\Application\Commands\CreateWarrantyClaimCommand;
use Domain\Warranty\Application\Handlers\CreateWarrantyClaimHandler;
use Domain\Warranty\Application\Queries\CustomerWarrantyClaimsQuery;
use Domain\Warranty\Application\Queries\WarrantyClaimDetailQuery;
use Domain\Warranty\Application\Queries\WarrantyPolicyDirectoryQuery;
use Domain\Warranty\Domain\Exceptions\InvalidWarrantyClaimSource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class WarrantyClaimController
{
    use FlashesToast;

    public function __construct(
        private readonly CustomerWarrantyClaimsQuery $claims,
        private readonly WarrantyClaimDetailQuery $detail,
        private readonly WarrantyPolicyDirectoryQuery $policies,
        private readonly CreateWarrantyClaimHandler $createClaim,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Customer/Warranty/Claims/Index', [
            'claims' => $this->claims->paginate(
                customerId: $request->user('customer')->id,
                page: $request->integer('page', 1),
            ),
        ]);
    }

    /** Reached from a "File a warranty claim" link on the customer's own Order/Repair detail page — the customer picks a policy, not the origin (that's fixed by which page they came from). Ownership of the origin is re-verified server-side on submit regardless of what these query params say. */
    public function create(Request $request): Response
    {
        return Inertia::render('Customer/Warranty/Claims/Create', [
            'policies' => $this->policies->all(),
            'originatingSaleId' => $request->integer('sale') ?: null,
            'originatingRepairJobId' => $request->integer('repair') ?: null,
        ]);
    }

    public function store(CreateWarrantyClaimRequest $request): RedirectResponse
    {
        try {
            $id = $this->createClaim->handle(new CreateWarrantyClaimCommand(
                warrantyPolicyId: $request->integer('warranty_policy_id'),
                originatingSaleId: $request->integer('originating_sale_id') ?: null,
                originatingRepairJobId: $request->integer('originating_repair_job_id') ?: null,
                customerId: $request->user('customer')->id,
                submittedByStaffId: null,
            ));
        } catch (InvalidWarrantyClaimSource $exception) {
            throw ValidationException::withMessages(['originating_sale_id' => $exception->getMessage()]);
        }

        $this->flashSuccess('Claim submitted', 'We\'ll notify you once it has been assessed.');

        return redirect()->route('customer.warranty.claims.show', ['claim' => $id]);
    }

    public function show(Request $request, int $claim): Response
    {
        $data = $this->detail->find($claim);

        abort_if($data === null || $data['customer_id'] !== $request->user('customer')->id, 403);

        return Inertia::render('Customer/Warranty/Claims/Show', ['claim' => $data]);
    }
}
