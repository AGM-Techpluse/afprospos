<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Concerns\FlashesToast;
use App\Http\Requests\Customer\AddCartItemRequest;
use App\Http\Requests\Customer\UpdateCartItemQuantityRequest;
use Domain\Inventory\Application\Contracts\StorefrontCatalogQuery;
use Domain\Inventory\Domain\Exceptions\InsufficientAvailableStock;
use Domain\Inventory\Domain\Exceptions\ItemNotReservable;
use Domain\Sales\Application\Commands\AddCheckoutItemCommand;
use Domain\Sales\Application\Commands\CancelCheckoutCommand;
use Domain\Sales\Application\Commands\CreateCheckoutCommand;
use Domain\Sales\Application\Commands\RemoveCheckoutItemCommand;
use Domain\Sales\Application\Commands\UpdateCheckoutItemQuantityCommand;
use Domain\Sales\Application\Handlers\AddCheckoutItemHandler;
use Domain\Sales\Application\Handlers\CancelCheckoutHandler;
use Domain\Sales\Application\Handlers\CreateCheckoutHandler;
use Domain\Sales\Application\Handlers\RemoveCheckoutItemHandler;
use Domain\Sales\Application\Handlers\UpdateCheckoutItemQuantityHandler;
use Domain\Sales\Application\Queries\CheckoutDetailQuery;
use Domain\Sales\Domain\Exceptions\CheckoutNotOpen;
use Domain\Sales\Domain\Exceptions\CustomerCartReservedAtAnotherShop;
use Domain\Sales\Domain\Exceptions\SerializedItemQuantityMustBeOne;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The customer-facing cart — "add to cart" on the storefront through to
 * an open, self-service `SalesCheckout` (no cashier). Payment/completion
 * is not this controller's job: a customer pays by bank transfer via
 * `Customer\PaymentController`, or an in-person till claims this same
 * cart (`CreateCheckoutHandler`'s claim branch) and completes it exactly
 * like any other POS sale.
 */
final class ShopController
{
    use FlashesToast;

    public function __construct(
        private readonly CheckoutDetailQuery $checkoutDetail,
        private readonly StorefrontCatalogQuery $catalog,
        private readonly CreateCheckoutHandler $createCheckout,
        private readonly AddCheckoutItemHandler $addItem,
        private readonly UpdateCheckoutItemQuantityHandler $updateItemQuantity,
        private readonly RemoveCheckoutItemHandler $removeItem,
        private readonly CancelCheckoutHandler $cancelCheckout,
    ) {}

    public function cart(Request $request): Response
    {
        return Inertia::render('Customer/Shop/Cart', [
            'cart' => $this->checkoutDetail->findOpenForCustomer($request->user('customer')->id),
        ]);
    }

    public function addItem(AddCartItemRequest $request): RedirectResponse
    {
        $shopId = $request->integer('shop_id');
        $skuId = $request->integer('sku_id');

        $product = $this->catalog->find($skuId);
        $sellableHere = $product !== null && collect($product['shops_available'])->contains('shop_id', $shopId);

        if (! $sellableHere) {
            throw ValidationException::withMessages(['shop_id' => 'This product is not available for pickup at that shop.']);
        }

        try {
            $checkoutId = $this->createCheckout->handle(new CreateCheckoutCommand(
                shopId: $shopId,
                customerId: $request->user('customer')->id,
                cashierStaffId: null,
            ));

            $this->addItem->handle(new AddCheckoutItemCommand(
                checkoutId: $checkoutId->value,
                skuId: $skuId,
                quantity: $request->integer('quantity', 1),
            ));
        } catch (CustomerCartReservedAtAnotherShop|InsufficientAvailableStock|ItemNotReservable|SerializedItemQuantityMustBeOne|CheckoutNotOpen $exception) {
            throw ValidationException::withMessages(['sku_id' => $exception->getMessage()]);
        }

        $this->flashSuccess('Added to your cart');

        return redirect()->back();
    }

    public function updateItemQuantity(UpdateCartItemQuantityRequest $request, int $item): RedirectResponse
    {
        $cart = $this->ownCartContainingItem($request, $item);

        try {
            $this->updateItemQuantity->handle(new UpdateCheckoutItemQuantityCommand(
                checkoutId: $cart['id'],
                checkoutItemId: $item,
                quantity: $request->integer('quantity'),
            ));
        } catch (InsufficientAvailableStock|ItemNotReservable|SerializedItemQuantityMustBeOne|CheckoutNotOpen $exception) {
            throw ValidationException::withMessages(['quantity' => $exception->getMessage()]);
        }

        return redirect()->back();
    }

    public function removeItem(Request $request, int $item): RedirectResponse
    {
        $cart = $this->ownCartContainingItem($request, $item);

        try {
            $this->removeItem->handle(new RemoveCheckoutItemCommand($cart['id'], $item));
        } catch (CheckoutNotOpen $exception) {
            throw ValidationException::withMessages(['item' => $exception->getMessage()]);
        }

        return redirect()->back();
    }

    public function cancelCart(Request $request): RedirectResponse
    {
        $cart = $this->checkoutDetail->findOpenForCustomer($request->user('customer')->id);
        abort_if($cart === null, 404);

        $this->cancelCheckout->handle(new CancelCheckoutCommand($cart['id']));
        $this->flashWarning('Cart cleared');

        return redirect()->back();
    }

    /**
     * Ownership is proven by membership, not by trusting a checkout id
     * from the request — only item ids are ever accepted from the
     * client, so there's nothing to spoof into targeting someone else's
     * checkout.
     *
     * @return array<string, mixed>
     */
    private function ownCartContainingItem(Request $request, int $itemId): array
    {
        $cart = $this->checkoutDetail->findOpenForCustomer($request->user('customer')->id);
        abort_if($cart === null, 404);
        abort_unless(collect($cart['items'])->contains('id', $itemId), 404);

        return $cart;
    }
}
