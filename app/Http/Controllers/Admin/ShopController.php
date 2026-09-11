<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\CreateShopRequest;
use App\Http\Requests\Admin\UpdateShopRequest;
use Domain\Shared\Application\DTOs\ActorContext;
use Domain\Shop\Application\Commands\CreateShopCommand;
use Domain\Shop\Application\Commands\SwitchActiveShopCommand;
use Domain\Shop\Application\Commands\UpdateShopCommand;
use Domain\Shop\Application\Handlers\CreateShopHandler;
use Domain\Shop\Application\Handlers\SwitchActiveShopHandler;
use Domain\Shop\Application\Handlers\UpdateShopHandler;
use Domain\Shop\Application\Queries\ShopDirectoryQuery;
use Domain\Shop\Domain\Exceptions\DuplicateShopSkuPrefixCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The accessible-shops list and active shop are ALSO shared Inertia
 * props on every Admin page (see HandleInertiaRequests) for the
 * shop-switcher — index()/show()/edit() here are the separate,
 * unfiltered Admin management screens (shops.view/shops.edit gated),
 * not that switcher.
 */
final class ShopController
{
    public function __construct(
        private readonly ShopDirectoryQuery $shopDirectory,
        private readonly CreateShopHandler $createShop,
        private readonly UpdateShopHandler $updateShop,
        private readonly SwitchActiveShopHandler $switchShop,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Shops/Index', [
            'shops' => $this->shopDirectory->paginate(
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
        return Inertia::render('Admin/Shops/Create');
    }

    public function show(int $shop): Response
    {
        $shopDetail = $this->shopDirectory->find($shop);

        abort_if($shopDetail === null, 404);

        return Inertia::render('Admin/Shops/Show', [
            'shop' => $shopDetail,
        ]);
    }

    public function edit(int $shop): Response
    {
        $shopDetail = $this->shopDirectory->find($shop);

        abort_if($shopDetail === null, 404);

        return Inertia::render('Admin/Shops/Edit', [
            'shop' => $shopDetail,
        ]);
    }

    public function update(UpdateShopRequest $request, int $shop): RedirectResponse
    {
        $actor = app(ActorContext::class);

        try {
            $this->updateShop->handle(new UpdateShopCommand(
                shopId: $shop,
                name: $request->string('name')->toString(),
                skuPrefixCode: strtoupper($request->string('sku_prefix_code')->toString()),
                address: $request->string('address')->toString(),
                contactPhone: $request->string('contact_phone')->toString(),
                contactEmail: $request->string('contact_email')->toString(),
                updatedByStaffId: $actor->staffId->value,
            ));
        } catch (DuplicateShopSkuPrefixCode $exception) {
            throw ValidationException::withMessages(['sku_prefix_code' => $exception->getMessage()]);
        }

        return redirect()->route('admin.shops.show', $shop);
    }

    public function store(CreateShopRequest $request): RedirectResponse
    {
        $actor = app(ActorContext::class);

        try {
            $this->createShop->handle(new CreateShopCommand(
                name: $request->string('name')->toString(),
                skuPrefixCode: strtoupper($request->string('sku_prefix_code')->toString()),
                address: $request->string('address')->toString(),
                contactPhone: $request->string('contact_phone')->toString(),
                contactEmail: $request->string('contact_email')->toString(),
                offlinePolicy: [],
                createdByStaffId: $actor->staffId->value,
            ));
        } catch (DuplicateShopSkuPrefixCode $exception) {
            throw ValidationException::withMessages(['sku_prefix_code' => $exception->getMessage()]);
        }

        return redirect()->route('admin.shops.index');
    }

    public function switch(Request $request): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->switchShop->handle(new SwitchActiveShopCommand(
            staffId: $actor->staffId->value,
            requestedShopId: $request->integer('shop_id'),
        ));

        return back();
    }
}
