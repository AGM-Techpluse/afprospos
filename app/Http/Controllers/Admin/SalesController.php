<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\FlashesToast;
use App\Http\Requests\Admin\AddCheckoutItemRequest;
use App\Http\Requests\Admin\ApplyDiscountRequest;
use App\Http\Requests\Admin\CompleteSaleRequest;
use App\Http\Requests\Admin\CreateCheckoutRequest;
use App\Http\Requests\Admin\UpdateCheckoutItemQuantityRequest;
use Domain\Identity\Application\Contracts\CustomerDirectoryQuery;
use Domain\Inventory\Application\Contracts\InventoryCatalogQuery;
use Domain\Inventory\Application\Queries\InventoryMasterListQuery;
use Domain\Inventory\Domain\Exceptions\InsufficientAvailableStock;
use Domain\Inventory\Domain\Exceptions\ItemNotReservable;
use Domain\Payments\Application\Contracts\PaymentTransactionLookup;
use Domain\Sales\Application\Commands\AddCheckoutItemCommand;
use Domain\Sales\Application\Commands\ApplyDiscountCommand;
use Domain\Sales\Application\Commands\ApproveCheckoutDiscountCommand;
use Domain\Sales\Application\Commands\CancelCheckoutCommand;
use Domain\Sales\Application\Commands\CreateCheckoutCommand;
use Domain\Sales\Application\Commands\CreateSaleFromPaidCheckoutCommand;
use Domain\Sales\Application\Commands\RemoveCheckoutItemCommand;
use Domain\Sales\Application\Commands\UpdateCheckoutItemQuantityCommand;
use Domain\Sales\Application\Handlers\AddCheckoutItemHandler;
use Domain\Sales\Application\Handlers\ApplyDiscountHandler;
use Domain\Sales\Application\Handlers\ApproveCheckoutDiscountHandler;
use Domain\Sales\Application\Handlers\CancelCheckoutHandler;
use Domain\Sales\Application\Handlers\CreateCheckoutHandler;
use Domain\Sales\Application\Handlers\CreateSaleFromPaidCheckoutHandler;
use Domain\Sales\Application\Handlers\RemoveCheckoutItemHandler;
use Domain\Sales\Application\Handlers\UpdateCheckoutItemQuantityHandler;
use Domain\Sales\Application\Queries\CheckoutDetailQuery;
use Domain\Sales\Application\Queries\SaleDetailQuery;
use Domain\Sales\Application\Queries\SalesHistoryQuery;
use Domain\Sales\Domain\Exceptions\CheckoutNotOpen;
use Domain\Sales\Domain\Exceptions\CheckoutReservationExpired;
use Domain\Sales\Domain\Exceptions\DiscountNotApproved;
use Domain\Sales\Domain\Exceptions\NoAdjustmentToApprove;
use Domain\Sales\Domain\Exceptions\SerializedItemQuantityMustBeOne;
use Domain\Sales\Domain\Exceptions\WarehouseCannotSell;
use Domain\Shared\Application\DTOs\ActorContext;
use Domain\Shop\Application\Queries\AccessibleShopsQuery;
use Domain\Shop\Application\Queries\ShopDirectoryQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class SalesController
{
    use FlashesToast;

    public function __construct(
        private readonly SalesHistoryQuery $history,
        private readonly SaleDetailQuery $saleDetail,
        private readonly CheckoutDetailQuery $checkoutDetail,
        private readonly InventoryCatalogQuery $catalog,
        private readonly InventoryMasterListQuery $masterLists,
        private readonly CustomerDirectoryQuery $customerDirectory,
        private readonly ShopDirectoryQuery $shops,
        private readonly AccessibleShopsQuery $accessibleShops,
        private readonly PaymentTransactionLookup $paymentLookup,
        private readonly CreateCheckoutHandler $createCheckout,
        private readonly AddCheckoutItemHandler $addItem,
        private readonly RemoveCheckoutItemHandler $removeItem,
        private readonly UpdateCheckoutItemQuantityHandler $updateItemQuantity,
        private readonly ApplyDiscountHandler $applyDiscount,
        private readonly ApproveCheckoutDiscountHandler $approveDiscount,
        private readonly CancelCheckoutHandler $cancelCheckout,
        private readonly CreateSaleFromPaidCheckoutHandler $completeSale,
    ) {}

    public function index(Request $request): Response
    {
        $actor = app(ActorContext::class);
        $shopId = $request->integer('shop_id') ?: null;
        abort_if($shopId !== null && ! $actor->canAccessShop($shopId), 403);

        return Inertia::render('Admin/Sales/Index', [
            'sales' => $this->history->paginate(
                search: $request->string('search')->toString() ?: null,
                shopId: $shopId,
                page: $request->integer('page', 1),
                restrictToShopIds: $actor->isOwner ? null : $actor->shopIds,
            ),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'shop_id' => $shopId,
            ],
        ]);
    }

    public function show(int $sale): Response
    {
        $actor = app(ActorContext::class);
        $data = $this->saleDetail->find($sale);

        abort_if($data === null, 404);
        abort_unless($actor->canAccessShop((int) $data['shop_id']), 403);

        return Inertia::render('Admin/Sales/Show', ['sale' => $data]);
    }

    public function receipt(int $sale): Response
    {
        $actor = app(ActorContext::class);
        $data = $this->saleDetail->find($sale);

        abort_if($data === null, 404);
        abort_unless($actor->canAccessShop((int) $data['shop_id']), 403);

        return Inertia::render('Admin/Sales/Receipt', ['sale' => $data]);
    }

    /** Empty-cart/chooser screen when no checkout param is given yet; otherwise the live cart. */
    public function checkout(Request $request): Response|RedirectResponse
    {
        $checkoutId = $request->integer('checkout') ?: null;
        $actor = app(ActorContext::class);
        $openCheckouts = [];

        if ($checkoutId === null) {
            $openCheckouts = $this->checkoutDetail->listOpenForCashier($actor->staffId->value, $actor->shopIds);

            // Exactly one open checkout is still the common case — resume it with zero friction, same as before.
            // Two or more means the cashier deliberately has several going; let them pick instead of guessing.
            if (count($openCheckouts) === 1) {
                return redirect()->route('admin.sales.checkout', ['checkout' => $openCheckouts[0]['id']]);
            }
        }

        $checkout = $checkoutId !== null ? $this->checkoutDetail->find($checkoutId) : null;
        abort_if($checkout !== null && ! $actor->canAccessShop((int) $checkout['shop_id']), 403);

        // Also needed while viewing a specific checkout (not just the chooser screen) — the
        // "leave this checkout?" guard reads these ids to tell "switching to one of my other
        // open checkouts" apart from "actually navigating away". Not part of the poll's own
        // `only` list below, so it's computed once per full page load, not every 5s.
        if ($checkout !== null) {
            $openCheckouts = $this->checkoutDetail->listOpenForCashier($actor->staffId->value, $actor->shopIds);
        }

        return Inertia::render('Admin/Sales/Checkout', [
            'checkout' => $checkout,
            'pendingPaymentTransaction' => $checkout !== null
                ? $this->paymentLookup->pendingTransactionFor('sales_checkout', $checkout['id'])
                : null,
            'openCheckouts' => $openCheckouts,
            'recentSales' => $checkout === null ? $this->history->recentForCashier($actor->staffId->value) : [],
            'todaysSales' => $checkout === null ? $this->history->todayForCashier($actor->staffId->value) : null,
            'shops' => $this->accessibleShops->forStaff($actor->staffId->value, $actor->isOwner),
            'productCategories' => array_column($this->masterLists->all()['categories'], 'name'),
            'productBrands' => $this->catalog->sellableBrands(),
            'checkoutReservationMinutes' => (int) config('afprospos.checkout_reservation_minutes'),
            'canApproveDiscount' => $actor->hasPermission('sales.approve-discount'),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $actor = app(ActorContext::class);
        $term = $request->string('q')->toString();
        $category = $request->string('category')->toString() ?: null;
        $brand = $request->string('brand')->toString() ?: null;
        $model = $request->string('model')->toString() ?: null;
        $shopId = $request->integer('shop_id');
        abort_unless($actor->canAccessShop($shopId), 403);

        // Any one of the four filters (text, category, brand, model) is enough to search; with none, nothing is shown.
        $hasFilter = $term !== '' || $category !== null || $brand !== null || $model !== null;

        return response()->json([
            'results' => $hasFilter ? $this->catalog->search($term, $shopId, 20, $category, $brand, $model) : [],
        ]);
    }

    /** Models for the checkout's variant filter, narrowed to whichever category/brand is already picked. */
    public function models(Request $request): JsonResponse
    {
        return response()->json([
            'results' => $this->catalog->sellableModels(
                $request->string('category')->toString() ?: null,
                $request->string('brand')->toString() ?: null,
            ),
        ]);
    }

    public function searchCustomers(Request $request): JsonResponse
    {
        $term = $request->string('q')->toString();

        return response()->json([
            'results' => $term !== '' ? $this->customerDirectory->search($term) : [],
        ]);
    }

    public function store(CreateCheckoutRequest $request): RedirectResponse
    {
        $actor = app(ActorContext::class);
        $shopId = $request->integer('shop_id');
        abort_unless($actor->canAccessShop($shopId), 403);

        try {
            $id = $this->createCheckout->handle(new CreateCheckoutCommand(
                shopId: $shopId,
                customerId: $request->integer('customer_id') ?: null,
                cashierStaffId: $actor->staffId->value,
                walkInName: $request->string('walk_in_name')->toString() ?: null,
                walkInPhone: $request->string('walk_in_phone')->toString() ?: null,
                walkInEmail: $request->string('walk_in_email')->toString() ?: null,
                forceNew: $request->boolean('force_new'),
            ));
        } catch (WarehouseCannotSell $exception) {
            throw ValidationException::withMessages(['shop_id' => $exception->getMessage()]);
        }

        return redirect()->route('admin.sales.checkout', ['checkout' => $id->value]);
    }

    public function addItem(AddCheckoutItemRequest $request, int $checkout): RedirectResponse
    {
        $this->assertCheckoutShopAccess(app(ActorContext::class), $checkout);

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

    public function updateItemQuantity(UpdateCheckoutItemQuantityRequest $request, int $checkout, int $item): RedirectResponse
    {
        $this->assertCheckoutShopAccess(app(ActorContext::class), $checkout);

        try {
            $this->updateItemQuantity->handle(new UpdateCheckoutItemQuantityCommand(
                checkoutId: $checkout,
                checkoutItemId: $item,
                quantity: $request->integer('quantity'),
            ));
        } catch (InsufficientAvailableStock|ItemNotReservable|SerializedItemQuantityMustBeOne|CheckoutNotOpen $exception) {
            throw ValidationException::withMessages(['quantity' => $exception->getMessage()]);
        }

        return redirect()->route('admin.sales.checkout', ['checkout' => $checkout]);
    }

    public function removeItem(int $checkout, int $item): RedirectResponse
    {
        $this->assertCheckoutShopAccess(app(ActorContext::class), $checkout);

        try {
            $this->removeItem->handle(new RemoveCheckoutItemCommand($checkout, $item));
        } catch (CheckoutNotOpen $exception) {
            throw ValidationException::withMessages(['item' => $exception->getMessage()]);
        }

        return redirect()->route('admin.sales.checkout', ['checkout' => $checkout]);
    }

    public function applyDiscount(ApplyDiscountRequest $request, int $checkout): RedirectResponse
    {
        $this->assertCheckoutShopAccess(app(ActorContext::class), $checkout);

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

    public function approveDiscount(int $checkout): RedirectResponse
    {
        $actor = app(ActorContext::class);
        $this->assertCheckoutShopAccess($actor, $checkout);

        try {
            $this->approveDiscount->handle(new ApproveCheckoutDiscountCommand(
                checkoutId: $checkout,
                approvedByStaffId: $actor->staffId->value,
            ));
        } catch (CheckoutNotOpen|NoAdjustmentToApprove $exception) {
            throw ValidationException::withMessages(['discount' => $exception->getMessage()]);
        }

        $this->flashSuccess('Discount approved');

        return redirect()->route('admin.sales.checkout', ['checkout' => $checkout]);
    }

    public function cancel(int $checkout): RedirectResponse
    {
        $this->assertCheckoutShopAccess(app(ActorContext::class), $checkout);

        $this->cancelCheckout->handle(new CancelCheckoutCommand($checkout));

        $this->flashWarning('Checkout cancelled');

        return redirect()->route('admin.sales.checkout');
    }

    public function complete(CompleteSaleRequest $request, int $checkout): RedirectResponse
    {
        $actor = app(ActorContext::class);
        $checkoutData = $this->checkoutDetail->find($checkout);

        abort_if($checkoutData === null, 404);
        abort_unless($actor->canAccessShop((int) $checkoutData['shop_id']), 403);

        $shop = $this->shops->find($checkoutData['shop_id']);

        abort_if($shop === null, 404);

        try {
            $saleId = $this->completeSale->handle(new CreateSaleFromPaidCheckoutCommand(
                checkoutId: $checkout,
                paymentMethod: $request->string('payment_method')->toString(),
                paymentReference: $request->string('payment_reference')->toString() ?: null,
                confirmedByStaffId: $actor->staffId->value,
                shopCode: $shop['sku_prefix_code'],
                existingPaymentTransactionId: $request->integer('existing_payment_transaction_id') ?: null,
            ));
        } catch (CheckoutReservationExpired|CheckoutNotOpen|DiscountNotApproved $exception) {
            throw ValidationException::withMessages(['payment_method' => $exception->getMessage()]);
        }

        $this->flashSuccess('Sale completed');

        return redirect()->route('admin.sales.receipt', ['sale' => $saleId]);
    }

    /**
     * BRD RBAC-05: every action on one specific checkout is blocked unless
     * the acting staff member (or the Shop Owner, who bypasses scope
     * entirely) was actually granted that checkout's shop — the
     * permission string alone only proves they hold the action in
     * general, never that this particular record is theirs to touch.
     *
     * @return array<string, mixed>
     */
    private function assertCheckoutShopAccess(ActorContext $actor, int $checkoutId): array
    {
        $checkout = $this->checkoutDetail->find($checkoutId);
        abort_if($checkout === null, 404);
        abort_unless($actor->canAccessShop((int) $checkout['shop_id']), 403);

        return $checkout;
    }
}
