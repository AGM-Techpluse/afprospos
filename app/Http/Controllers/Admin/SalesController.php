<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\AddCheckoutItemRequest;
use App\Http\Requests\Admin\ApplyDiscountRequest;
use App\Http\Requests\Admin\CompleteSaleRequest;
use App\Http\Requests\Admin\CreateCheckoutRequest;
use Domain\Inventory\Application\Contracts\InventoryCatalogQuery;
use Domain\Inventory\Domain\Exceptions\InsufficientAvailableStock;
use Domain\Inventory\Domain\Exceptions\ItemNotReservable;
use Domain\Sales\Application\Commands\AddCheckoutItemCommand;
use Domain\Sales\Application\Commands\ApplyDiscountCommand;
use Domain\Sales\Application\Commands\CancelCheckoutCommand;
use Domain\Sales\Application\Commands\CreateCheckoutCommand;
use Domain\Sales\Application\Commands\CreateSaleFromPaidCheckoutCommand;
use Domain\Sales\Application\Commands\RemoveCheckoutItemCommand;
use Domain\Sales\Application\Handlers\AddCheckoutItemHandler;
use Domain\Sales\Application\Handlers\ApplyDiscountHandler;
use Domain\Sales\Application\Handlers\CancelCheckoutHandler;
use Domain\Sales\Application\Handlers\CreateCheckoutHandler;
use Domain\Sales\Application\Handlers\CreateSaleFromPaidCheckoutHandler;
use Domain\Sales\Application\Handlers\RemoveCheckoutItemHandler;
use Domain\Sales\Application\Queries\CheckoutDetailQuery;
use Domain\Sales\Application\Queries\SaleDetailQuery;
use Domain\Sales\Application\Queries\SalesHistoryQuery;
use Domain\Sales\Domain\Exceptions\CheckoutNotOpen;
use Domain\Sales\Domain\Exceptions\CheckoutReservationExpired;
use Domain\Sales\Domain\Exceptions\SerializedItemQuantityMustBeOne;
use Domain\Shared\Application\DTOs\ActorContext;
use Domain\Shop\Application\Queries\ShopDirectoryQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class SalesController
{
    public function __construct(
        private readonly SalesHistoryQuery $history,
        private readonly SaleDetailQuery $saleDetail,
        private readonly CheckoutDetailQuery $checkoutDetail,
        private readonly InventoryCatalogQuery $catalog,
        private readonly ShopDirectoryQuery $shops,
        private readonly CreateCheckoutHandler $createCheckout,
        private readonly AddCheckoutItemHandler $addItem,
        private readonly RemoveCheckoutItemHandler $removeItem,
        private readonly ApplyDiscountHandler $applyDiscount,
        private readonly CancelCheckoutHandler $cancelCheckout,
        private readonly CreateSaleFromPaidCheckoutHandler $completeSale,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Sales/Index', [
            'sales' => $this->history->paginate(
                search: $request->string('search')->toString() ?: null,
                shopId: $request->integer('shop_id') ?: null,
                page: $request->integer('page', 1),
            ),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'shop_id' => $request->integer('shop_id') ?: null,
            ],
        ]);
    }

    public function show(int $sale): Response
    {
        $data = $this->saleDetail->find($sale);

        abort_if($data === null, 404);

        return Inertia::render('Admin/Sales/Show', ['sale' => $data]);
    }

    public function receipt(int $sale): Response
    {
        $data = $this->saleDetail->find($sale);

        abort_if($data === null, 404);

        return Inertia::render('Admin/Sales/Receipt', ['sale' => $data]);
    }

    /** Empty-cart screen when no checkout param is given yet; otherwise the live cart. */
    public function checkout(Request $request): Response
    {
        $checkoutId = $request->integer('checkout') ?: null;
        $checkout = $checkoutId !== null ? $this->checkoutDetail->find($checkoutId) : null;

        return Inertia::render('Admin/Sales/Checkout', [
            'checkout' => $checkout,
            'shops' => $this->shops->all(),
            'checkoutReservationMinutes' => (int) config('afprospos.checkout_reservation_minutes'),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $term = $request->string('q')->toString();
        $shopId = $request->integer('shop_id');

        return response()->json([
            'results' => $term !== '' ? $this->catalog->search($term, $shopId) : [],
        ]);
    }

    public function store(CreateCheckoutRequest $request): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $id = $this->createCheckout->handle(new CreateCheckoutCommand(
            shopId: $request->integer('shop_id'),
            customerId: $request->integer('customer_id') ?: null,
            cashierStaffId: $actor->staffId->value,
        ));

        return redirect()->route('admin.sales.checkout', ['checkout' => $id->value]);
    }

    public function addItem(AddCheckoutItemRequest $request, int $checkout): RedirectResponse
    {
        try {
            $this->addItem->handle(new AddCheckoutItemCommand(
                checkoutId: $checkout,
                skuId: $request->integer('sku_id'),
                quantity: $request->integer('quantity'),
            ));
        } catch (InsufficientAvailableStock|ItemNotReservable|SerializedItemQuantityMustBeOne|CheckoutNotOpen $exception) {
            throw ValidationException::withMessages(['sku_id' => $exception->getMessage()]);
        }

        return redirect()->route('admin.sales.checkout', ['checkout' => $checkout]);
    }

    public function removeItem(int $checkout, int $item): RedirectResponse
    {
        try {
            $this->removeItem->handle(new RemoveCheckoutItemCommand($checkout, $item));
        } catch (CheckoutNotOpen $exception) {
            throw ValidationException::withMessages(['item' => $exception->getMessage()]);
        }

        return redirect()->route('admin.sales.checkout', ['checkout' => $checkout]);
    }

    public function applyDiscount(ApplyDiscountRequest $request, int $checkout): RedirectResponse
    {
        try {
            $this->applyDiscount->handle(new ApplyDiscountCommand(
                checkoutId: $checkout,
                type: $request->string('type')->toString(),
                sourceId: $request->integer('source_id'),
                amountMinor: $request->integer('amount_minor'),
            ));
        } catch (CheckoutNotOpen $exception) {
            throw ValidationException::withMessages(['type' => $exception->getMessage()]);
        }

        return redirect()->route('admin.sales.checkout', ['checkout' => $checkout]);
    }

    public function cancel(int $checkout): RedirectResponse
    {
        $this->cancelCheckout->handle(new CancelCheckoutCommand($checkout));

        return redirect()->route('admin.sales.checkout');
    }

    public function complete(CompleteSaleRequest $request, int $checkout): RedirectResponse
    {
        $actor = app(ActorContext::class);
        $checkoutData = $this->checkoutDetail->find($checkout);

        abort_if($checkoutData === null, 404);

        $shop = $this->shops->find($checkoutData['shop_id']);

        abort_if($shop === null, 404);

        try {
            $saleId = $this->completeSale->handle(new CreateSaleFromPaidCheckoutCommand(
                checkoutId: $checkout,
                paymentMethod: $request->string('payment_method')->toString(),
                paymentReference: $request->string('payment_reference')->toString() ?: null,
                confirmedByStaffId: $actor->staffId->value,
                shopCode: $shop['sku_prefix_code'],
            ));
        } catch (CheckoutReservationExpired|CheckoutNotOpen $exception) {
            throw ValidationException::withMessages(['payment_method' => $exception->getMessage()]);
        }

        return redirect()->route('admin.sales.receipt', ['sale' => $saleId]);
    }
}
