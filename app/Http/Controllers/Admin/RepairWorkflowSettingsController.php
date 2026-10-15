<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\FlashesToast;
use App\Http\Requests\Admin\UpdateQcFailPolicyRequest;
use Domain\Repair\Application\Commands\UpdateQcFailPolicyCommand;
use Domain\Repair\Application\Handlers\UpdateQcFailPolicyHandler;
use Domain\Repair\Application\Queries\QcFailPolicyQuery;
use Domain\Shared\Application\DTOs\ActorContext;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/** Settings → Repair workflow: what happens to a repair when its quality check fails. */
final class RepairWorkflowSettingsController
{
    use FlashesToast;

    public function __construct(
        private readonly QcFailPolicyQuery $policy,
        private readonly UpdateQcFailPolicyHandler $update,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Settings/RepairWorkflow/Index', [
            'qcFailAction' => $this->policy->current(),
        ]);
    }

    public function update(UpdateQcFailPolicyRequest $request): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->update->handle(new UpdateQcFailPolicyCommand($request->string('action')->toString(), $actor->staffId->value));

        $this->flashSuccess('Repair workflow saved');

        return redirect()->back();
    }
}
