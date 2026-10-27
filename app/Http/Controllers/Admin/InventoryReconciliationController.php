<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\FlashesToast;
use App\Http\Requests\Admin\ReconcileStockCountsRequest;
use Domain\Inventory\Application\Commands\ReconcileStockCountsCommand;
use Domain\Inventory\Application\Handlers\ReconcileStockCountsHandler;
use Domain\Inventory\Application\Queries\InventoryMasterListQuery;
use Domain\Inventory\Application\Queries\StockLevelQuery;
use Domain\Shared\Application\DTOs\ActorContext;
use Domain\Shop\Application\Queries\AccessibleShopsQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Physical stock count vs. the system's recorded on-hand quantity, per product — non-serialized SKUs only (a serialized unit's discrepancy is handled individually via Products/Show, not here). */
final class InventoryReconciliationController
{
    use FlashesToast;

    /** Comfortably above any shop's realistic non-serialized catalog size — this page intentionally has no pagination UI, a category filter is the expected way to narrow a large shop's list. */
    private const MAX_ROWS = 1000;

    public function __construct(
        private readonly StockLevelQuery $stockLevels,
        private readonly InventoryMasterListQuery $masterLists,
        private readonly AccessibleShopsQuery $accessibleShops,
        private readonly ReconcileStockCountsHandler $reconcile,
    ) {}

    public function index(Request $request): Response
    {
        $actor = app(ActorContext::class);
        $shopId = $request->has('shop_id') ? ($request->integer('shop_id') ?: null) : $actor->activeShopId;
        abort_if($shopId !== null && ! $actor->canAccessShop($shopId), 403);

        $category = $request->string('category')->toString() ?: null;

        return Inertia::render('Admin/Inventory/Reconciliation/Index', [
            'stock' => $shopId !== null
                ? $this->stockLevels->paginate(search: null, shopId: $shopId, page: 1, perPage: self::MAX_ROWS, category: $category)['data']
                : [],
            'filters' => [
                'shop_id' => $shopId,
                'category' => $category,
            ],
            'shops' => $this->accessibleShops->forStaff($actor->staffId->value, $actor->isOwner),
            'categories' => array_column($this->masterLists->all()['categories'], 'name'),
            'reasons' => config('afprospos.stock_reconciliation_reasons'),
        ]);
    }

    public function store(ReconcileStockCountsRequest $request): RedirectResponse
    {
        $actor = app(ActorContext::class);

        foreach ($request->array('counts') as $count) {
            abort_unless($actor->canAccessShop((int) $count['shop_id']), 403);
        }

        $result = $this->reconcile->handle(new ReconcileStockCountsCommand(
            counts: $request->array('counts'),
            reconciledByStaffId: $actor->staffId->value,
        ));

        if ($result['failures'] === []) {
            $this->flashSuccess("Reconciled {$result['succeeded']} product(s)");
        } else {
            $this->flashWarning(
                "Reconciled {$result['succeeded']} product(s), ".count($result['failures']).' failed',
                implode(' ', $result['failures']),
            );
        }

        return redirect()->back();
    }
}
