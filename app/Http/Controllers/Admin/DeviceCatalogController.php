<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Repairs\DeviceCatalog\AttachSuggestedPartRequest;
use App\Http\Requests\Admin\Repairs\DeviceCatalog\SaveDeviceBrandRequest;
use App\Http\Requests\Admin\Repairs\DeviceCatalog\SaveDeviceProblemTagRequest;
use App\Http\Requests\Admin\Repairs\DeviceCatalog\SaveDeviceTypeRequest;
use Domain\Inventory\Application\Contracts\InventoryCatalogQuery;
use Domain\Repair\Application\Commands\AttachSuggestedPartCommand;
use Domain\Repair\Application\Commands\CreateDeviceBrandCommand;
use Domain\Repair\Application\Commands\CreateDeviceProblemTagCommand;
use Domain\Repair\Application\Commands\CreateDeviceTypeCommand;
use Domain\Repair\Application\Commands\DeleteDeviceBrandCommand;
use Domain\Repair\Application\Commands\DeleteDeviceProblemTagCommand;
use Domain\Repair\Application\Commands\DeleteDeviceTypeCommand;
use Domain\Repair\Application\Commands\DetachSuggestedPartCommand;
use Domain\Repair\Application\Commands\UpdateDeviceBrandCommand;
use Domain\Repair\Application\Commands\UpdateDeviceProblemTagCommand;
use Domain\Repair\Application\Commands\UpdateDeviceTypeCommand;
use Domain\Repair\Application\Handlers\AttachSuggestedPartHandler;
use Domain\Repair\Application\Handlers\CreateDeviceBrandHandler;
use Domain\Repair\Application\Handlers\CreateDeviceProblemTagHandler;
use Domain\Repair\Application\Handlers\CreateDeviceTypeHandler;
use Domain\Repair\Application\Handlers\DeleteDeviceBrandHandler;
use Domain\Repair\Application\Handlers\DeleteDeviceProblemTagHandler;
use Domain\Repair\Application\Handlers\DeleteDeviceTypeHandler;
use Domain\Repair\Application\Handlers\DetachSuggestedPartHandler;
use Domain\Repair\Application\Handlers\UpdateDeviceBrandHandler;
use Domain\Repair\Application\Handlers\UpdateDeviceProblemTagHandler;
use Domain\Repair\Application\Handlers\UpdateDeviceTypeHandler;
use Domain\Repair\Application\Queries\DeviceCatalogQuery;
use Domain\Shared\Application\DTOs\ActorContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Settings-level CRUD for the repair-intake device catalog (Device Type -> Brand -> Problem tag -> suggested Inventory SKUs). Also backs the inline quick-add affordances on Repairs/Create.tsx — same Commands, just posted from a modal instead of this page. */
final class DeviceCatalogController
{
    public function __construct(
        private readonly DeviceCatalogQuery $catalog,
        private readonly InventoryCatalogQuery $inventoryCatalog,
        private readonly CreateDeviceTypeHandler $createDeviceType,
        private readonly UpdateDeviceTypeHandler $updateDeviceType,
        private readonly DeleteDeviceTypeHandler $deleteDeviceType,
        private readonly CreateDeviceBrandHandler $createDeviceBrand,
        private readonly UpdateDeviceBrandHandler $updateDeviceBrand,
        private readonly DeleteDeviceBrandHandler $deleteDeviceBrand,
        private readonly CreateDeviceProblemTagHandler $createProblemTag,
        private readonly UpdateDeviceProblemTagHandler $updateProblemTag,
        private readonly DeleteDeviceProblemTagHandler $deleteProblemTag,
        private readonly AttachSuggestedPartHandler $attachSuggestedPart,
        private readonly DetachSuggestedPartHandler $detachSuggestedPart,
    ) {}

    public function index(): Response
    {
        $actor = app(ActorContext::class);

        return Inertia::render('Admin/Settings/DeviceCatalog/Index', [
            'deviceTypes' => $this->catalog->full($this->resolveShopId($actor)),
        ]);
    }

    public function storeType(SaveDeviceTypeRequest $request): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->createDeviceType->handle(new CreateDeviceTypeCommand(
            label: $request->string('label')->toString(),
            icon: $request->string('icon')->toString(),
            sortOrder: $request->integer('sort_order', 0),
            createdByStaffId: $actor->staffId->value,
        ));

        return redirect()->back();
    }

    public function updateType(SaveDeviceTypeRequest $request, int $type): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->updateDeviceType->handle(new UpdateDeviceTypeCommand(
            deviceTypeId: $type,
            label: $request->string('label')->toString(),
            icon: $request->string('icon')->toString(),
            sortOrder: $request->integer('sort_order', 0),
            updatedByStaffId: $actor->staffId->value,
        ));

        return redirect()->back();
    }

    public function destroyType(int $type): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->deleteDeviceType->handle(new DeleteDeviceTypeCommand($type, $actor->staffId->value));

        return redirect()->back();
    }

    public function storeBrand(SaveDeviceBrandRequest $request): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->createDeviceBrand->handle(new CreateDeviceBrandCommand(
            deviceTypeId: $request->integer('device_type_id'),
            name: $request->string('name')->toString(),
            sortOrder: $request->integer('sort_order', 0),
            createdByStaffId: $actor->staffId->value,
        ));

        return redirect()->back();
    }

    public function updateBrand(SaveDeviceBrandRequest $request, int $brand): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->updateDeviceBrand->handle(new UpdateDeviceBrandCommand(
            deviceBrandId: $brand,
            name: $request->string('name')->toString(),
            sortOrder: $request->integer('sort_order', 0),
            updatedByStaffId: $actor->staffId->value,
        ));

        return redirect()->back();
    }

    public function destroyBrand(int $brand): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->deleteDeviceBrand->handle(new DeleteDeviceBrandCommand($brand, $actor->staffId->value));

        return redirect()->back();
    }

    public function storeProblemTag(SaveDeviceProblemTagRequest $request): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->createProblemTag->handle(new CreateDeviceProblemTagCommand(
            deviceTypeId: $request->integer('device_type_id'),
            label: $request->string('label')->toString(),
            sortOrder: $request->integer('sort_order', 0),
            createdByStaffId: $actor->staffId->value,
        ));

        return redirect()->back();
    }

    public function updateProblemTag(SaveDeviceProblemTagRequest $request, int $tag): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->updateProblemTag->handle(new UpdateDeviceProblemTagCommand(
            deviceProblemTagId: $tag,
            label: $request->string('label')->toString(),
            sortOrder: $request->integer('sort_order', 0),
            updatedByStaffId: $actor->staffId->value,
        ));

        return redirect()->back();
    }

    public function destroyProblemTag(int $tag): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->deleteProblemTag->handle(new DeleteDeviceProblemTagCommand($tag, $actor->staffId->value));

        return redirect()->back();
    }

    public function attachSuggestedPart(AttachSuggestedPartRequest $request, int $tag): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->attachSuggestedPart->handle(new AttachSuggestedPartCommand(
            deviceProblemTagId: $tag,
            skuId: $request->integer('sku_id'),
            attachedByStaffId: $actor->staffId->value,
        ));

        return redirect()->back();
    }

    public function detachSuggestedPart(int $suggestedPart): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->detachSuggestedPart->handle(new DetachSuggestedPartCommand($suggestedPart, $actor->staffId->value));

        return redirect()->back();
    }

    /** Backs the "attach a suggested part" search box on the settings page — same live-search pattern as Sales/Repairs' own part search. */
    public function searchParts(Request $request): JsonResponse
    {
        $actor = app(ActorContext::class);
        $term = $request->string('q')->toString();
        $shopId = $this->resolveShopId($actor);

        return response()->json([
            'results' => $term !== '' && $shopId !== null ? $this->inventoryCatalog->search($term, $shopId) : [],
        ]);
    }

    private function resolveShopId(ActorContext $actor): ?int
    {
        return $actor->activeShopId ?? ($actor->shopIds[0] ?? null);
    }
}
