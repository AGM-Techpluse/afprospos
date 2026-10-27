<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\FlashesToast;
use App\Http\Requests\Admin\UpdateInventoryItemStatusRequest;
use Domain\Inventory\Application\Commands\UpdateInventoryItemStatusCommand;
use Domain\Inventory\Application\Handlers\UpdateInventoryItemStatusHandler;
use Domain\Inventory\Application\Queries\SerializedUnitsQuery;
use Domain\Inventory\Domain\Exceptions\InventoryItemStatusTransitionNotAllowed;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryItemRecord;
use Domain\Shared\Application\DTOs\ActorContext;
use Domain\Shop\Application\Queries\AccessibleShopsQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class InventorySerializedUnitsController
{
    use FlashesToast;

    public function __construct(
        private readonly SerializedUnitsQuery $units,
        private readonly AccessibleShopsQuery $accessibleShops,
        private readonly UpdateInventoryItemStatusHandler $updateStatus,
    ) {}

    public function index(Request $request): Response
    {
        $actor = app(ActorContext::class);
        $search = $request->string('search')->toString() ?: null;
        $shopId = $request->integer('shop_id') ?: null;
        $status = $request->string('status')->toString() ?: null;

        abort_if($shopId !== null && ! $actor->canAccessShop($shopId), 403);

        return Inertia::render('Admin/Inventory/SerializedUnits/Index', [
            'units' => $this->units->paginate(
                search: $search,
                shopId: $shopId,
                status: $status,
                page: $request->integer('page', 1),
            ),
            'filters' => [
                'search' => $search ?? '',
                'shop_id' => $shopId,
                'status' => $status ?? '',
            ],
            'shops' => $this->accessibleShops->forStaff($actor->staffId->value, $actor->isOwner),
        ]);
    }

    public function updateStatus(UpdateInventoryItemStatusRequest $request, int $item): RedirectResponse
    {
        $actor = app(ActorContext::class);
        $record = InventoryItemRecord::query()->findOrFail($item);
        abort_unless($actor->canAccessShop($record->current_shop_id), 403);

        try {
            $this->updateStatus->handle(new UpdateInventoryItemStatusCommand(
                inventoryItemId: $item,
                toStatus: $request->string('to_status')->toString(),
                note: $request->string('note')->toString() ?: null,
                updatedByStaffId: $actor->staffId->value,
            ));
        } catch (InventoryItemStatusTransitionNotAllowed $exception) {
            throw ValidationException::withMessages(['to_status' => $exception->getMessage()]);
        }

        $this->flashSuccess($request->string('to_status')->toString() === 'damaged' ? 'Unit marked damaged' : 'Unit restored to available');

        return redirect()->back();
    }
}
