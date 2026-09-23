<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Domain\Inventory\Application\Queries\SerializedUnitsQuery;
use Domain\Shared\Application\DTOs\ActorContext;
use Domain\Shop\Application\Queries\AccessibleShopsQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class InventorySerializedUnitsController
{
    public function __construct(
        private readonly SerializedUnitsQuery $units,
        private readonly AccessibleShopsQuery $accessibleShops,
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
}
