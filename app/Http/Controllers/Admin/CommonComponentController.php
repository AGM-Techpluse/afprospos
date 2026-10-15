<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\FlashesToast;
use App\Http\Requests\Admin\SaveCommonComponentRequest;
use Domain\Repair\Application\Commands\CreateCommonComponentCommand;
use Domain\Repair\Application\Commands\DeleteCommonComponentCommand;
use Domain\Repair\Application\Commands\UpdateCommonComponentCommand;
use Domain\Repair\Application\Handlers\CreateCommonComponentHandler;
use Domain\Repair\Application\Handlers\DeleteCommonComponentHandler;
use Domain\Repair\Application\Handlers\UpdateCommonComponentHandler;
use Domain\Repair\Application\Queries\CommonComponentCatalogQuery;
use Domain\Shared\Application\DTOs\ActorContext;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/** Settings-level CRUD for the quick-check component catalog offered at diagnosis-case intake — flat, device-type-agnostic, unlike DeviceCatalogController's nested Type->Brand->ProblemTag tree. */
final class CommonComponentController
{
    use FlashesToast;

    public function __construct(
        private readonly CommonComponentCatalogQuery $catalog,
        private readonly CreateCommonComponentHandler $createComponent,
        private readonly UpdateCommonComponentHandler $updateComponent,
        private readonly DeleteCommonComponentHandler $deleteComponent,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Settings/CommonComponents/Index', [
            'components' => $this->catalog->list(),
        ]);
    }

    public function store(SaveCommonComponentRequest $request): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->createComponent->handle(new CreateCommonComponentCommand(
            label: $request->string('label')->toString(),
            sortOrder: $request->integer('sort_order', 0),
            createdByStaffId: $actor->staffId->value,
            commissionAmountMinor: $request->filled('commission_amount_minor') ? $request->integer('commission_amount_minor') : null,
        ));

        $this->flashSuccess('Common component created');

        return redirect()->back();
    }

    public function update(SaveCommonComponentRequest $request, int $component): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->updateComponent->handle(new UpdateCommonComponentCommand(
            commonComponentId: $component,
            label: $request->string('label')->toString(),
            sortOrder: $request->integer('sort_order', 0),
            updatedByStaffId: $actor->staffId->value,
            commissionAmountMinor: $request->filled('commission_amount_minor') ? $request->integer('commission_amount_minor') : null,
        ));

        $this->flashSuccess('Common component updated');

        return redirect()->back();
    }

    public function destroy(int $component): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->deleteComponent->handle(new DeleteCommonComponentCommand($component, $actor->staffId->value));

        $this->flashSuccess('Common component deleted');

        return redirect()->back();
    }
}
