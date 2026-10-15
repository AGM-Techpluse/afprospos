<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\FlashesToast;
use App\Http\Requests\Admin\AddProductVariantRequest;
use App\Http\Requests\Admin\CreateProductRequest;
use App\Http\Requests\Admin\CreateProductWithVariantsRequest;
use App\Http\Requests\Admin\UpdateSkuCommissionRequest;
use Domain\Inventory\Application\Commands\AddProductVariantCommand;
use Domain\Inventory\Application\Commands\CreateProductCommand;
use Domain\Inventory\Application\Commands\CreateProductWithVariantsCommand;
use Domain\Inventory\Application\Commands\UpdateSkuCommissionCommand;
use Domain\Inventory\Application\DTOs\VariantInput;
use Domain\Inventory\Application\Handlers\AddProductVariantHandler;
use Domain\Inventory\Application\Handlers\CreateProductHandler;
use Domain\Inventory\Application\Handlers\CreateProductWithVariantsHandler;
use Domain\Inventory\Application\Handlers\UpdateSkuCommissionHandler;
use Domain\Inventory\Application\Queries\InventoryMasterListQuery;
use Domain\Inventory\Application\Queries\ProductCatalogQuery;
use Domain\Inventory\Domain\Exceptions\DuplicateSkuCode;
use Domain\Inventory\Domain\Exceptions\ImeiAlreadyExists;
use Domain\Inventory\Domain\Exceptions\UnknownMasterEntry;
use Domain\Shared\Application\DTOs\ActorContext;
use Domain\Shop\Application\Queries\AccessibleShopsQuery;
use Domain\Shop\Application\Queries\ShopDirectoryQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response as ResponseFactory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class ProductController
{
    use FlashesToast;

    public function __construct(
        private readonly ProductCatalogQuery $catalog,
        private readonly ShopDirectoryQuery $shops,
        private readonly AccessibleShopsQuery $accessibleShops,
        private readonly CreateProductHandler $createProduct,
        private readonly CreateProductWithVariantsHandler $createProductWithVariants,
        private readonly AddProductVariantHandler $addVariant,
        private readonly InventoryMasterListQuery $masterLists,
        private readonly UpdateSkuCommissionHandler $updateSkuCommission,
    ) {}

    public function index(Request $request): Response
    {
        $actor = app(ActorContext::class);
        $shopId = $request->has('shop_id') ? ($request->integer('shop_id') ?: null) : $actor->activeShopId;
        abort_if($shopId !== null && ! $actor->canAccessShop($shopId), 403);

        return Inertia::render('Admin/Inventory/Products/Index', [
            'products' => $this->catalog->paginate(
                search: $request->string('search')->toString() ?: null,
                category: $request->string('category')->toString() ?: null,
                brand: $request->string('brand')->toString() ?: null,
                page: $request->integer('page', 1),
                shopId: $shopId,
                purpose: $request->string('purpose')->toString() ?: null,
            ),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'category' => $request->string('category')->toString(),
                'brand' => $request->string('brand')->toString(),
                'shop_id' => $shopId,
            ],
            'shops' => $this->accessibleShops->forStaff($actor->staffId->value, $actor->isOwner),
            'categories' => $this->catalog->categories(),
        ]);
    }

    public function create(): Response
    {
        $actor = app(ActorContext::class);
        $masters = $this->masterLists->all();

        return Inertia::render('Admin/Inventory/Products/Create', [
            'shops' => $this->accessibleShops->forStaff($actor->staffId->value, $actor->isOwner),
            'categories' => $masters['categories'],
            'units' => $masters['units'],
            'suppliers' => $masters['suppliers'],
        ]);
    }

    public function show(int $product): Response
    {
        $actor = app(ActorContext::class);
        $sku = $this->catalog->find($product);

        abort_if($sku === null, 404);

        return Inertia::render('Admin/Inventory/Products/Show', [
            'sku' => $sku,
            'shops' => $this->accessibleShops->forStaff($actor->staffId->value, $actor->isOwner),
        ]);
    }

    public function image(int $product): \Symfony\Component\HttpFoundation\Response
    {
        $sku = $this->catalog->find($product);

        abort_if($sku === null || $sku['image_path'] === null, 404);

        $disk = Storage::disk('public');
        abort_unless($disk->exists($sku['image_path']), 404);

        return ResponseFactory::make($disk->get($sku['image_path']), 200, [
            'Content-Type' => $disk->mimeType($sku['image_path']) ?: 'image/jpeg',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /** The add-variant form for an already-existing Product — reached from Products/Show's "Add variant" action, keyed by product id (not a Sku id — see ProductCatalogQuery::findProduct). */
    public function addVariantForm(int $product): Response
    {
        $actor = app(ActorContext::class);
        $productData = $this->catalog->findProduct($product);

        abort_if($productData === null, 404);

        return Inertia::render('Admin/Inventory/Products/AddVariant', [
            'product' => $productData,
            'shops' => $this->accessibleShops->forStaff($actor->staffId->value, $actor->isOwner),
            'isSerialized' => request()->boolean('serialized'),
        ]);
    }

    public function store(CreateProductRequest $request): RedirectResponse
    {
        $actor = app(ActorContext::class);
        $shop = $this->shops->find($request->integer('shop_id'));

        abort_if($shop === null, 404);
        abort_unless($actor->canAccessShop($shop['id']), 403);

        $imagePath = $request->hasFile('product_image')
            ? $request->file('product_image')->store('inventory/products', 'public')
            : null;

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
                initialSerialNumbers: $request->array('initial_serial_numbers') ?: null,
                createdByStaffId: $actor->staffId->value,
                unit: $request->string('unit')->toString() ?: 'pcs',
                purpose: $request->string('purpose')->toString() ?: 'sale',
                supplierId: $request->integer('supplier_id') ?: null,
                imagePath: $imagePath,
                isCommissionable: $request->boolean('is_commissionable'),
                commissionAmountMinor: $request->filled('commission_amount_minor') ? $request->integer('commission_amount_minor') : null,
            ));
        } catch (DuplicateSkuCode|ImeiAlreadyExists $exception) {
            if ($imagePath !== null) {
                Storage::disk('public')->delete($imagePath);
            }

            throw ValidationException::withMessages(['brand' => $exception->getMessage()]);
        } catch (UnknownMasterEntry $exception) {
            if ($imagePath !== null) {
                Storage::disk('public')->delete($imagePath);
            }

            throw ValidationException::withMessages(['category' => $exception->getMessage()]);
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

        $imagePath = $request->hasFile('product_image')
            ? $request->file('product_image')->store('inventory/products', 'public')
            : null;

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
                    initialImeis: isset($variant['initial_imeis']) ? array_values(array_filter($variant['initial_imeis'])) : null,
                    initialSerialNumbers: isset($variant['initial_serial_numbers']) ? array_values($variant['initial_serial_numbers']) : null,
                    isCommissionable: (bool) ($variant['is_commissionable'] ?? false),
                    commissionAmountMinor: isset($variant['commission_amount_minor']) ? (int) $variant['commission_amount_minor'] : null,
                ), $request->array('variants')),
                createdByStaffId: $actor->staffId->value,
                unit: $request->string('unit')->toString() ?: 'pcs',
                purpose: $request->string('purpose')->toString() ?: 'sale',
                supplierId: $request->integer('supplier_id') ?: null,
                isSerialized: $request->boolean('is_serialized'),
                imagePath: $imagePath,
            ));
        } catch (DuplicateSkuCode|ImeiAlreadyExists $exception) {
            if ($imagePath !== null) {
                Storage::disk('public')->delete($imagePath);
            }

            throw ValidationException::withMessages(['brand' => $exception->getMessage()]);
        } catch (UnknownMasterEntry $exception) {
            if ($imagePath !== null) {
                Storage::disk('public')->delete($imagePath);
            }

            throw ValidationException::withMessages(['category' => $exception->getMessage()]);
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
                isSerialized: $request->boolean('is_serialized'),
                initialQuantity: $request->integer('initial_quantity') ?: null,
                initialImeis: $request->array('initial_imeis') ?: null,
                initialSerialNumbers: $request->array('initial_serial_numbers') ?: null,
                createdByStaffId: $actor->staffId->value,
                isCommissionable: $request->boolean('is_commissionable'),
                commissionAmountMinor: $request->filled('commission_amount_minor') ? $request->integer('commission_amount_minor') : null,
            ));
        } catch (DuplicateSkuCode $exception) {
            throw ValidationException::withMessages(['attributes' => $exception->getMessage()]);
        }

        $this->flashSuccess('Variant added');

        return redirect()->route('admin.inventory.products.show', $skuId->value);
    }

    public function updateCommission(UpdateSkuCommissionRequest $request, int $sku): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->updateSkuCommission->handle(new UpdateSkuCommissionCommand(
            skuId: $sku,
            isCommissionable: $request->boolean('is_commissionable'),
            commissionAmountMinor: $request->filled('commission_amount_minor') ? $request->integer('commission_amount_minor') : null,
            updatedByStaffId: $actor->staffId->value,
        ));

        $this->flashSuccess('Commission settings updated');

        return redirect()->back();
    }
}
