<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use Domain\Sales\Application\Queries\CheckoutDetailQuery;
use Domain\Sales\Application\Queries\CustomerOrdersQuery;
use Domain\Sales\Application\Queries\SaleDetailQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Read-only order visibility (BRD SALE-03's "payable in the customer's dashboard") — no payment-initiation flow, that's Payments' (Phase 5) job. */
final class OrdersController
{
    public function __construct(
        private readonly CustomerOrdersQuery $orders,
        private readonly CheckoutDetailQuery $checkoutDetail,
        private readonly SaleDetailQuery $saleDetail,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Customer/Orders/Index', [
            'orders' => $this->orders->paginate(
                customerId: $request->user('customer')->id,
                page: $request->integer('page', 1),
            ),
        ]);
    }

    public function showCheckout(Request $request, int $checkout): Response
    {
        $data = $this->checkoutDetail->find($checkout);

        abort_if($data === null || $data['customer_id'] !== $request->user('customer')->id, 403);

        return Inertia::render('Customer/Orders/Show', ['type' => 'checkout', 'order' => $data]);
    }

    public function showSale(Request $request, int $sale): Response
    {
        $data = $this->saleDetail->find($sale);

        abort_if($data === null || $data['customer_id'] !== $request->user('customer')->id, 403);

        return Inertia::render('Customer/Orders/Show', ['type' => 'sale', 'order' => $data]);
    }
}
