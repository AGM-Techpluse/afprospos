<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\FlashesToast;
use App\Http\Requests\Admin\AddProductVariantRequest;
use App\Http\Requests\Admin\CreateProductRequest;
use App\Http\Requests\Admin\CreateProductWithVariantsRequest;
use Domain\Inventory\Application\Commands\AddProductVariantCommand;
use Domain\Inventory\Application\Commands\CreateProductCommand;
use Domain\Inventory\Application\Commands\CreateProductWithVariantsCommand;
use Domain\Inventory\Application\DTOs\VariantInput;
use Domain\Inventory\Application\Handlers\AddProductVariantHandler;
use Domain\Inventory\Application\Handlers\CreateProductHandler;
use Domain\Inventory\Application\Handlers\CreateProductWithVariantsHandler;
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
    use FlashesToast;

    public function __construct(
        private readonly ProductCatalogQuery $catalog,
        private readonly ShopDirectoryQuery $shops,
        private readonly CreateProductHandler $createProduct,
        private readonly CreateProductWithVariantsHandler $createProductWithVariants,
        private readonly AddProductVariantHandler $addVariant,
    ) {}

    public function index(Request $request): Response
    {
        $actor = app(ActorContext::class);
        $shopId = $request->has('shop_id') ? ($request->integer('shop_id') ?: null) : $actor->activeShopId;

        return Inertia::render('Admin/Inventory/Products/Index', [
            'products' => $this->catalog->paginate(
                search: $request->string('search')->toString() ?: null,
                category: $request->string('category')->toString() ?: null,
                brand: $request->string('brand')->toString() ?: null,
                page: $request->integer('page', 1),
                shopId: $shopId,
            ),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'category' => $request->string('category')->toString(),
                'brand' => $request->string('brand')->toString(),
                'shop_id' => $shopId,
            ],
            'shops' => $this->shops->all(),
            'categories' => $this->catalog->categories(),
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

    /** The add-variant form for an already-existing Product — reached from Products/Show's "Add variant" action, keyed by product id (not a Sku id — see ProductCatalogQuery::findProduct). */
    public function addVariantForm(int $product): Response
    {
        $productData = $this->catalog->findProduct($product);

        abort_if($productData === null, 404);

        return Inertia::render('Admin/Inventory/Products/AddVariant', [
            'product' => $productData,
            'shops' => $this->shops->all(),
        ]);
    }

    public function store(CreateProductRequest $request): RedirectResponse
    {
        $actor = app(ActorContext::class);
        $shop = $this->shops->find($request->integer('shop_id'));

        abort_if($shop === null, 404);
        abort_unless($actor->canAccessShop($shop['id']), 403);

        try {
            $skuId = $this->createProduct->handle(new CreateProductCommand(
                brand: $request->string('brand')->toString(),
                model: $request->string('model')->toString(),
                category: $request->string('category')->toString(),
                attributes: $request->array('attributes') ?: [],
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

        $this->flashSuccess('Product created');

        return redirect()->route('admin.inventory.products.show', $skuId->value);
    }

    /** INV-BR-17: creates one Product with several non-serialized Sku variants in one submission (e.g. a screenguard with one Sku per brand). */
    public function storeWithVariants(CreateProductWithVariantsRequest $request): RedirectResponse
    {
        $actor = app(ActorContext::class);
        $shop = $this->shops->find($request->integer('shop_id'));

        abort_if($shop === null, 404);
        abort_unless($actor->canAccessShop($shop['id']), 403);

        try {
            $productId = $this->createProductWithVariants->handle(new CreateProductWithVariantsCommand(
                brand: $request->string('brand')->toString(),
                model: $request->string('model')->toString(),
                category: $request->string('category')->toString(),
                shopCode: $shop['sku_prefix_code'],
                shopId: $shop['id'],
                condition: $request->string('condition')->toString(),
                variants: array_map(static fn (array $variant): VariantInput => new VariantInput(
                    attributes: $variant['attributes'] ?? [],
                    costPriceMinor: (int) $variant['cost_price_minor'],
                    markupPercent: (float) $variant['markup_percent'],
                    sellingPriceMinorOverride: isset($variant['selling_price_minor_override']) ? (int) $variant['selling_price_minor_override'] : null,
                    lowStockThreshold: isset($variant['low_stock_threshold']) ? (int) $variant['low_stock_threshold'] : null,
                    initialQuantity: isset($variant['initial_quantity']) ? (int) $variant['initial_quantity'] : null,
                ), $request->array('variants')),
                createdByStaffId: $actor->staffId->value,
            ));
        } catch (DuplicateSkuCode $exception) {
            throw ValidationException::withMessages(['brand' => $exception->getMessage()]);
        }

        $this->flashSuccess('Product created with variants');

        return redirect()->route('admin.inventory.products.index', ['search' => $request->string('brand')->toString()]);
    }

    /** INV-BR-18: adds a new variant to an already-existing Product. */
    public function storeVariant(AddProductVariantRequest $request, int $product): RedirectResponse
    {
        $actor = app(ActorContext::class);
        $shop = $this->shops->find($request->integer('shop_id'));

        abort_if($shop === null, 404);
        abort_unless($actor->canAccessShop($shop['id']), 403);

        try {
            $skuId = $this->addVariant->handle(new AddProductVariantCommand(
                productId: $product,
                attributes: $request->array('attributes') ?: [],
                costPriceMinor: $request->integer('cost_price_minor'),
                markupPercent: (float) $request->input('markup_percent'),
                sellingPriceMinorOverride: $request->integer('selling_price_minor_override') ?: null,
                lowStockThreshold: $request->integer('low_stock_threshold') ?: null,
                shopCode: $shop['sku_prefix_code'],
                shopId: $shop['id'],
                condition: 'new',
                initialQuantity: $request->integer('initial_quantity') ?: null,
                createdByStaffId: $actor->staffId->value,
            ));
        } catch (DuplicateSkuCode $exception) {
            throw ValidationException::withMessages(['attributes' => $exception->getMessage()]);
        }

        $this->flashSuccess('Variant added');

        return redirect()->route('admin.inventory.products.show', $skuId->value);
    }
}
