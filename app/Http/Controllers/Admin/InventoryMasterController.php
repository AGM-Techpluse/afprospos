<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\FlashesToast;
use App\Http\Requests\Admin\StoreInventoryMasterRequest;
use App\Http\Requests\Admin\UpdateInventoryMasterRequest;
use Domain\Inventory\Application\Commands\CreateMasterEntryCommand;
use Domain\Inventory\Application\Commands\DeleteMasterEntryCommand;
use Domain\Inventory\Application\Commands\RenameMasterEntryCommand;
use Domain\Inventory\Application\Handlers\CreateMasterEntryHandler;
use Domain\Inventory\Application\Handlers\DeleteMasterEntryHandler;
use Domain\Inventory\Application\Handlers\RenameMasterEntryHandler;
use Domain\Inventory\Application\Queries\InventoryMasterListQuery;
use Domain\Inventory\Domain\Exceptions\MasterEntryAlreadyExists;
use Domain\Inventory\Domain\Exceptions\MasterEntryInUse;
use Domain\Inventory\Domain\Exceptions\MasterEntryNotFound;
use Domain\Shared\Application\DTOs\ActorContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin-managed categories, base units and suppliers. Products are filed under
 * these, so the product form's dropdowns and the Settings → Inventory catalog
 * page both read from here.
 */
final class InventoryMasterController
{
    use FlashesToast;

    public function __construct(
        private readonly InventoryMasterListQuery $lists,
        private readonly CreateMasterEntryHandler $create,
        private readonly RenameMasterEntryHandler $rename,
        private readonly DeleteMasterEntryHandler $delete,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Settings/InventoryCatalog/Index', $this->lists->all());
    }

    public function store(StoreInventoryMasterRequest $request): RedirectResponse
    {
        $actor = app(ActorContext::class);

        try {
            $this->create->handle(new CreateMasterEntryCommand(
                kind: $request->string('kind')->toString(),
                name: $request->string('name')->toString(),
                contactPhone: $request->string('contact_phone')->toString() ?: null,
                contactEmail: $request->string('contact_email')->toString() ?: null,
                createdByStaffId: $actor->staffId->value,
            ));
        } catch (MasterEntryAlreadyExists $exception) {
            throw ValidationException::withMessages(['name' => $exception->getMessage()]);
        }

        $this->flashSuccess('Added to the inventory list');

        return redirect()->back();
    }

    public function update(UpdateInventoryMasterRequest $request, int $entry): RedirectResponse
    {
        $actor = app(ActorContext::class);

        try {
            $this->rename->handle(new RenameMasterEntryCommand(
                entryId: $entry,
                name: $request->string('name')->toString(),
                renamedByStaffId: $actor->staffId->value,
            ));
        } catch (MasterEntryAlreadyExists $exception) {
            throw ValidationException::withMessages(['name' => $exception->getMessage()]);
        } catch (MasterEntryNotFound) {
            abort(404);
        }

        $this->flashSuccess('Renamed');

        return redirect()->back();
    }

    public function destroy(int $entry): RedirectResponse
    {
        $actor = app(ActorContext::class);

        try {
            $this->delete->handle(new DeleteMasterEntryCommand(entryId: $entry, deletedByStaffId: $actor->staffId->value));
        } catch (MasterEntryInUse $exception) {
            throw ValidationException::withMessages(['name' => $exception->getMessage()]);
        } catch (MasterEntryNotFound) {
            abort(404);
        }

        $this->flashSuccess('Removed');

        return redirect()->back();
    }
}
