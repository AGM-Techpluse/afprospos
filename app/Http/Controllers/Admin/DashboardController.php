<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Domain\Audit\Application\Queries\RecentAuditEventsQuery;
use Domain\Inventory\Application\Queries\InventoryHealthQuery;
use Domain\Payments\Application\Queries\PaymentTransactionsQuery;
use Domain\Repair\Application\Queries\RepairsListQuery;
use Domain\Sales\Application\Queries\SalesHistoryQuery;
use Domain\Shared\Application\DTOs\ActorContext;
use Domain\Shop\Application\Queries\ShopAnalyticsQuery;
use Inertia\Inertia;
use Inertia\Response;

/**
 * UI/UX §14C.9 / §14B.8: stat row (Open repairs, Today's sales, Pending
 * disputes, Low-stock items) + a chart card + a full-width "needs
 * attention" list is the written spec; recent-sales/recent-repairs,
 * inventory health, and login activity are additive per the business's
 * own request, gated behind the same permission each already requires
 * on its own dedicated page so nobody sees a number for a module they
 * cannot open.
 */
final class DashboardController
{
    public function __construct(
        private readonly RepairsListQuery $repairs,
        private readonly SalesHistoryQuery $sales,
        private readonly PaymentTransactionsQuery $payments,
        private readonly InventoryHealthQuery $inventoryHealth,
        private readonly RecentAuditEventsQuery $auditEvents,
        private readonly ShopAnalyticsQuery $shopAnalytics,
    ) {}

    public function __invoke(): Response
    {
        $actor = app(ActorContext::class);
        $shopId = $actor->activeShopId;

        // The active shop's own name/id isn't re-fetched here — HandleInertiaRequests
        // already shares it on every Admin page as shopSwitcher.active, so the page
        // reads that instead of paying for a second, redundant query for the same row.

        $canViewRepairs = $actor->hasPermission('repairs.view');
        $canCreateRepairs = $actor->hasPermission('repairs.create');
        $canViewSales = $actor->hasPermission('sales.view');
        $canCreateSales = $actor->hasPermission('sales.create');
        $canViewPayments = $actor->hasPermission('payments.view');
        $canViewInventory = $actor->hasPermission('inventory.view');
        $canViewShops = $actor->hasPermission('shops.view');
        $canViewAudit = $actor->hasPermission('audit.view');

        return Inertia::render('Admin/Dashboard/Index', [
            'quickActions' => [
                'canCreateRepair' => $canCreateRepairs,
                'canCreateSale' => $canCreateSales,
                'canViewShops' => $canViewShops,
                'activeShopId' => $shopId,
            ],
            'stats' => [
                'openRepairs' => $canViewRepairs ? $this->repairs->countOpen($shopId) : null,
                'todaysSales' => $canViewSales ? $this->sales->todayForShop($shopId) : null,
                'pendingDisputes' => $canViewPayments ? $this->payments->countByStatus('disputed') : null,
                'inventoryHealth' => $canViewInventory ? $this->inventoryHealth->forShop($shopId) : null,
            ],
            'recentRepairs' => $canViewRepairs ? $this->repairs->recent($shopId, 5) : [],
            'recentSales' => $canViewSales ? $this->sales->recent($shopId, 5) : [],
            'loginActivity' => $canViewAudit ? $this->auditEvents->recentLoginActivity(8) : null,
            'shopAnalytics' => $shopId !== null ? $this->shopAnalytics->forShop($shopId) : null,
        ]);
    }
}
