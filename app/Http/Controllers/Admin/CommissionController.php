<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Domain\Commission\Application\Queries\CommissionReportQuery;
use Domain\Identity\Application\Queries\StaffDirectoryQuery;
use Domain\Shared\Application\DTOs\ActorContext;
use Domain\Shop\Application\Queries\AccessibleShopsQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class CommissionController
{
    public function __construct(
        private readonly CommissionReportQuery $report,
        private readonly StaffDirectoryQuery $staff,
        private readonly AccessibleShopsQuery $accessibleShops,
    ) {}

    public function index(Request $request): Response
    {
        $actor = app(ActorContext::class);
        $shopId = $request->integer('shop_id') ?: null;
        abort_if($shopId !== null && ! $actor->canAccessShop($shopId), 403);

        $staffId = $request->integer('staff_id') ?: null;
        $fromDate = $request->string('from_date')->toString() ?: null;
        $toDate = $request->string('to_date')->toString() ?: null;

        return Inertia::render('Admin/Commission/Index', [
            'entries' => $this->report->paginate(
                staffId: $staffId,
                shopId: $shopId,
                fromDate: $fromDate,
                toDate: $toDate,
                page: $request->integer('page', 1),
                restrictToShopIds: $actor->isOwner ? null : $actor->shopIds,
            ),
            'filters' => [
                'staff_id' => $staffId,
                'shop_id' => $shopId,
                'from_date' => $fromDate,
                'to_date' => $toDate,
            ],
            'shops' => $this->accessibleShops->forStaff($actor->staffId->value, $actor->isOwner),
            'staffOptions' => $this->staff->paginate(search: null, status: 'active', role: null, page: 1, perPage: 200)['data'],
        ]);
    }
}
