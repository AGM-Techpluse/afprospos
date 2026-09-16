<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Domain\Inventory\Application\Queries\SerializedUnitsQuery;
use Domain\Shop\Application\Queries\ShopDirectoryQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class InventorySerializedUnitsController
{
    public function __construct(
        private readonly SerializedUnitsQuery $units,
        private readonly ShopDirectoryQuery $shops,
    ) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString() ?: null;
        $shopId = $request->integer('shop_id') ?: null;
        $status = $request->string('status')->toString() ?: null;

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
            'shops' => $this->shops->all(),
        ]);
    }
}
