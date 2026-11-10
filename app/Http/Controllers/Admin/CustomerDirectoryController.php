<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Domain\Identity\Application\Contracts\CustomerDirectoryQuery;
use Domain\Repair\Application\Queries\CustomerRepairsQuery;
use Domain\Sales\Application\Queries\CustomerOrdersQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Every customer, registered or walk-in-only — BRD-adjacent stakeholder ask: a place to see who's been recorded during sales/repairs without a self-service account. */
final class CustomerDirectoryController
{
    public function __construct(
        private readonly CustomerDirectoryQuery $customers,
        private readonly CustomerOrdersQuery $orders,
        private readonly CustomerRepairsQuery $repairs,
    ) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $registration = $request->string('registration')->toString();

        return Inertia::render('Admin/Customers/Index', [
            'customers' => $this->customers->paginate(
                search: $search ?: null,
                registration: $registration ?: null,
                page: $request->integer('page', 1),
            ),
            'filters' => [
                'search' => $search,
                'registration' => $registration,
            ],
        ]);
    }

    /**
     * Composes Identity's own record with Sales' and Repair's own
     * customer-scoped queries (CustomerOrdersQuery/CustomerRepairsQuery —
     * the exact same two queries the Customer Portal's self-service "my
     * orders"/"my repairs" pages already use) — this composition has to
     * happen here, not inside Identity, which must never reach into
     * another module's tables (CPNC §0).
     */
    public function show(Request $request, int $customer): Response
    {
        $customerData = $this->customers->find($customer);

        abort_if($customerData === null, 404);

        return Inertia::render('Admin/Customers/Show', [
            'customer' => $customerData,
            'orderSummary' => $this->orders->summary($customer),
            'repairCount' => $this->repairs->count($customer),
            'orders' => $this->orders->paginate($customer, $request->integer('orders_page', 1)),
            'repairs' => $this->repairs->paginate($customer, $request->integer('repairs_page', 1)),
        ]);
    }
}
