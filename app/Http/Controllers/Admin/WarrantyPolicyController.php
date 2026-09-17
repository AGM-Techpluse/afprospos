<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\FlashesToast;
use App\Http\Requests\Admin\Warranty\SaveWarrantyPolicyRequest;
use Domain\Shared\Application\DTOs\ActorContext;
use Domain\Warranty\Application\Commands\CreateWarrantyPolicyCommand;
use Domain\Warranty\Application\Commands\DeleteWarrantyPolicyCommand;
use Domain\Warranty\Application\Commands\UpdateWarrantyPolicyCommand;
use Domain\Warranty\Application\Handlers\CreateWarrantyPolicyHandler;
use Domain\Warranty\Application\Handlers\DeleteWarrantyPolicyHandler;
use Domain\Warranty\Application\Handlers\UpdateWarrantyPolicyHandler;
use Domain\Warranty\Application\Queries\WarrantyPolicyDirectoryQuery;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/** WAR-BR-01: configurable warranty policies (coverage/exclusions/remedies) — admin-only settings CRUD, no lifecycle beyond create/update/delete. */
final class WarrantyPolicyController
{
    use FlashesToast;

    public function __construct(
        private readonly WarrantyPolicyDirectoryQuery $directory,
        private readonly CreateWarrantyPolicyHandler $createPolicy,
        private readonly UpdateWarrantyPolicyHandler $updatePolicy,
        private readonly DeleteWarrantyPolicyHandler $deletePolicy,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Warranty/Policies/Index', [
            'policies' => $this->directory->all(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Warranty/Policies/Create');
    }

    public function store(SaveWarrantyPolicyRequest $request): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->createPolicy->handle(new CreateWarrantyPolicyCommand(
            name: $request->string('name')->toString(),
            coverageDurationDays: $request->integer('coverage_duration_days'),
            coverageStartPoint: $request->string('coverage_start_point')->toString(),
            coveredScope: $request->array('covered_scope'),
            exclusions: $request->array('exclusions') ?? [],
            availableRemedies: $request->array('available_remedies'),
            coverageExtent: $request->string('coverage_extent')->toString(),
            createdByStaffId: $actor->staffId->value,
        ));

        $this->flashSuccess('Warranty policy created');

        return redirect()->route('admin.warranty.policies.index');
    }

    public function edit(int $policy): Response
    {
        $policyDetail = $this->directory->find($policy);

        abort_if($policyDetail === null, 404);

        return Inertia::render('Admin/Warranty/Policies/Edit', [
            'policy' => $policyDetail,
        ]);
    }

    public function update(SaveWarrantyPolicyRequest $request, int $policy): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->updatePolicy->handle(new UpdateWarrantyPolicyCommand(
            warrantyPolicyId: $policy,
            name: $request->string('name')->toString(),
            coverageDurationDays: $request->integer('coverage_duration_days'),
            coverageStartPoint: $request->string('coverage_start_point')->toString(),
            coveredScope: $request->array('covered_scope'),
            exclusions: $request->array('exclusions') ?? [],
            availableRemedies: $request->array('available_remedies'),
            coverageExtent: $request->string('coverage_extent')->toString(),
            updatedByStaffId: $actor->staffId->value,
        ));

        $this->flashSuccess('Warranty policy updated');

        return redirect()->route('admin.warranty.policies.index');
    }

    public function destroy(int $policy): RedirectResponse
    {
        $actor = app(ActorContext::class);

        try {
            $this->deletePolicy->handle(new DeleteWarrantyPolicyCommand($policy, $actor->staffId->value));
        } catch (QueryException $exception) {
            throw ValidationException::withMessages(['policy' => 'This policy cannot be deleted while claims still reference it.']);
        }

        $this->flashSuccess('Warranty policy deleted');

        return redirect()->route('admin.warranty.policies.index');
    }
}
