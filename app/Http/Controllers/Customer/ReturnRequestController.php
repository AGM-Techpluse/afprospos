<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Concerns\FlashesToast;
use App\Http\Requests\Customer\Warranty\CreateReturnRequestRequest;
use Domain\Sales\Application\Queries\CustomerOrdersQuery;
use Domain\Warranty\Application\Commands\CreateReturnRequestCommand;
use Domain\Warranty\Application\Handlers\CreateReturnRequestHandler;
use Domain\Warranty\Application\Queries\CustomerReturnRequestsQuery;
use Domain\Warranty\Application\Queries\ReturnRequestDetailQuery;
use Domain\Warranty\Domain\Exceptions\ReturnDoesNotBelongToCustomer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class ReturnRequestController
{
    use FlashesToast;

    public function __construct(
        private readonly CustomerReturnRequestsQuery $returns,
        private readonly ReturnRequestDetailQuery $detail,
        private readonly CustomerOrdersQuery $orders,
        private readonly CreateReturnRequestHandler $createReturn,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Customer/Warranty/Returns/Index', [
            'returns' => $this->returns->paginate(
                customerId: $request->user('customer')->id,
                page: $request->integer('page', 1),
            ),
        ]);
    }

    /** Reached from a "Start a return" link on the customer's own Order detail page, or from the Returns index's "New return" action — either way the customer picks which of their own completed sales to return. */
    public function create(Request $request): Response
    {
        $orders = $this->orders->paginate(customerId: $request->user('customer')->id, page: 1)['data'];

        return Inertia::render('Customer/Warranty/Returns/Create', [
            'saleId' => $request->integer('sale') ?: null,
            'recentSales' => array_values(array_filter($orders, static fn (array $order): bool => $order['type'] === 'sale')),
        ]);
    }

    public function store(CreateReturnRequestRequest $request): RedirectResponse
    {
        try {
            $id = $this->createReturn->handle(new CreateReturnRequestCommand(
                saleId: $request->integer('sale_id'),
                customerId: $request->user('customer')->id,
                submittedByStaffId: null,
            ));
        } catch (ReturnDoesNotBelongToCustomer $exception) {
            throw ValidationException::withMessages(['sale_id' => $exception->getMessage()]);
        }

        $this->flashSuccess('Return requested', "We'll notify you once it has been assessed.");

        return redirect()->route('customer.warranty.returns.show', ['returnRequest' => $id]);
    }

    public function show(Request $request, int $returnRequest): Response
    {
        $data = $this->detail->find($returnRequest);

        abort_if($data === null || $data['customer_id'] !== $request->user('customer')->id, 403);

        return Inertia::render('Customer/Warranty/Returns/Show', ['returnRequest' => $data]);
    }
}
