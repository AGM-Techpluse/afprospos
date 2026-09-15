<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use Domain\Repair\Application\Queries\CustomerRepairsQuery;
use Domain\Repair\Application\Queries\RepairJobDetailQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Read-only repair tracker (Implementation Plan Phase 6 UI list) — mirrors Customer\OrdersController exactly, no customer-initiated actions. */
final class RepairsController
{
    public function __construct(
        private readonly CustomerRepairsQuery $repairs,
        private readonly RepairJobDetailQuery $detail,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Customer/Repairs/Index', [
            'repairs' => $this->repairs->paginate(
                customerId: $request->user('customer')->id,
                page: $request->integer('page', 1),
            ),
        ]);
    }

    public function show(Request $request, int $repair): Response
    {
        $data = $this->detail->find($repair);

        abort_if($data === null || $data['customer_id'] !== $request->user('customer')->id, 403);

        return Inertia::render('Customer/Repairs/Show', ['repair' => $data]);
    }
}
