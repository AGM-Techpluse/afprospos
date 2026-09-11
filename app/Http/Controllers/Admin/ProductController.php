<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\CreateProductRequest;
use Domain\Inventory\Application\Commands\CreateProductCommand;
use Domain\Inventory\Application\Handlers\CreateProductHandler;
use Domain\Inventory\Application\Queries\ProductCatalogQuery;
use Domain\Inventory\Domain\Exceptions\DuplicateSkuCode;
use Domain\Inventory\Domain\Exceptions\ImeiAlreadyExists;
use Domain\Shared\Application\DTOs\ActorContext;
use Domain\Shop\Application\Queries\ShopDirectoryQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class ProductController
{
    public function __construct(
        private readonly ProductCatalogQuery $catalog,
        private readonly ShopDirectoryQuery $shops,
        private readonly CreateProductHandler $createProduct,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Inventory/Products/Index', [
            'products' => $this->catalog->paginate(
                search: $request->string('search')->toString() ?: null,
                category: $request->string('category')->toString() ?: null,
                brand: $request->string('brand')->toString() ?: null,
                page: $request->integer('page', 1),
            ),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'category' => $request->string('category')->toString(),
                'brand' => $request->string('brand')->toString(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Inventory/Products/Create', [
            'shops' => $this->shops->all(),
        ]);
    }

    public function show(int $product): Response
    {
        $sku = $this->catalog->find($product);

        abort_if($sku === null, 404);

        return Inertia::render('Admin/Inventory/Products/Show', [
            'sku' => $sku,
            'shops' => $this->shops->all(),
        ]);
    }

    public function store(CreateProductRequest $request): RedirectResponse
    {
        $actor = app(ActorContext::class);
        $shop = $this->shops->find($request->integer('shop_id'));

        abort_if($shop === null, 404);

        try {
            $skuId = $this->createProduct->handle(new CreateProductCommand(
                brand: $request->string('brand')->toString(),
                model: $request->string('model')->toString(),
                category: $request->string('category')->toString(),
                attributes: [],
                isSerialized: $request->boolean('is_serialized'),
                costPriceMinor: $request->integer('cost_price_minor'),
                markupPercent: (float) $request->input('markup_percent'),
                sellingPriceMinorOverride: $request->integer('selling_price_minor_override') ?: null,
                lowStockThreshold: $request->integer('low_stock_threshold') ?: null,
                shopCode: $shop['sku_prefix_code'],
                shopId: $shop['id'],
                condition: $request->string('condition')->toString(),
                initialQuantity: $request->integer('initial_quantity') ?: null,
                initialImeis: $request->array('initial_imeis') ?: null,
                createdByStaffId: $actor->staffId->value,
            ));
        } catch (DuplicateSkuCode|ImeiAlreadyExists $exception) {
            throw ValidationException::withMessages(['brand' => $exception->getMessage()]);
        }

        return redirect()->route('admin.inventory.products.show', $skuId->value);
    }
}
