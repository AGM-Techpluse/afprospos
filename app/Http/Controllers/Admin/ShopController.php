<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\CreateShopRequest;
use Domain\Shared\Application\DTOs\ActorContext;
use Domain\Shop\Application\Commands\CreateShopCommand;
use Domain\Shop\Application\Commands\SwitchActiveShopCommand;
use Domain\Shop\Application\Handlers\CreateShopHandler;
use Domain\Shop\Application\Handlers\SwitchActiveShopHandler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * No index() here: the accessible-shops list and active shop are shared
 * Inertia props on every Admin page (see HandleInertiaRequests in the
 * Phase 1 guide, Step 10), not a standalone page — Phase 2 builds the
 * actual shop-switcher UI component that consumes those shared props.
 */
final class ShopController
{
    public function __construct(
        private readonly CreateShopHandler $createShop,
        private readonly SwitchActiveShopHandler $switchShop,
    ) {}

    public function store(CreateShopRequest $request): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->createShop->handle(new CreateShopCommand(
            name: $request->string('name')->toString(),
            skuPrefixCode: strtoupper($request->string('sku_prefix_code')->toString()),
            address: $request->string('address')->toString(),
            contactPhone: $request->string('contact_phone')->toString(),
            contactEmail: $request->string('contact_email')->toString(),
            offlinePolicy: [],
            createdByStaffId: $actor->staffId->value,
        ));

        return back();
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
