import { Head, router } from '@inertiajs/react';
import AdminShell from '../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../Components/Admin/AdminPageHead';
import AdminButton from '../../../Components/Admin/AdminButton';
import AdminStatCard from '../../../Components/Admin/AdminStatCard';
import AdminBadge, { AdminBadgeStatus } from '../../../Components/Admin/AdminBadge';
import AdminEmptyState from '../../../Components/Admin/AdminEmptyState';
import Icon from '../../../Components/Icons/Icon';
import RevenueChart, { RevenuePoint } from '../../../Features/Shop/RevenueChart';
import RevenueBreakdownChart, { RevenueBreakdownSlice } from '../../../Features/Shop/RevenueBreakdownChart';
import { formatNaira } from '../../../lib/money';

type RepairRow = {
    id: number;
    device_make: string;
    device_model: string;
    repair_status: string;
    created_at: string;
};

type SaleRow = {
    id: number;
    invoice_number: string;
    total_minor: number;
    payment_method: string;
    created_at: string;
};

type LoginEvent = {
    id: number;
    actor_staff_id: number | null;
    actor_name: string | null;
    created_at: string;
};

interface DashboardProps {
    quickActions: {
        canCreateRepair: boolean;
        canCreateSale: boolean;
        canViewShops: boolean;
        activeShopId: number | null;
    };
    stats: {
        openRepairs: number | null;
        todaysSales: { sales_count: number; total_minor: number } | null;
        pendingDisputes: number | null;
        inventoryHealth: { total_skus: number; low_stock_count: number; out_of_stock_count: number } | null;
    };
    recentRepairs: RepairRow[];
    recentSales: SaleRow[];
    loginActivity: LoginEvent[];
    shopAnalytics: { revenue_series: RevenuePoint[]; revenue_breakdown: RevenueBreakdownSlice[] } | null;
    activeShop: { id: number; name: string } | null;
}

const REPAIR_STATUS_BADGE: Record<string, AdminBadgeStatus> = {
    received: 'neutral',
    diagnosing: 'info',
    awaiting_authorization: 'warning',
    payment_overdue: 'warning',
    expired_cancelled: 'danger',
    awaiting_parts: 'info',
    in_progress: 'info',
    completed: 'success',
    unrepairable: 'danger',
    failed_requires_resolution: 'danger',
};

function healthStatus(health: DashboardProps['stats']['inventoryHealth']): { label: string; status: AdminBadgeStatus } {
    if (!health) return { label: 'Unknown', status: 'neutral' };
    if (health.total_skus === 0) return { label: 'No inventory yet', status: 'neutral' };
    if (health.out_of_stock_count > 0) return { label: 'Needs attention', status: 'danger' };
    if (health.low_stock_count > 0) return { label: 'Watch closely', status: 'warning' };
    return { label: 'Healthy', status: 'success' };
}

export default function Dashboard({ quickActions, stats, recentRepairs, recentSales, loginActivity, shopAnalytics, activeShop }: DashboardProps) {
    const health = healthStatus(stats.inventoryHealth);

    return (
        <AdminShell>
            <Head title="Dashboard" />

            <AdminPageHead
                title="Dashboard"
                description={activeShop ? `Overview for ${activeShop.name}` : 'Overview across every shop you can access.'}
            />

            <div className="grid gap-4 lg:grid-cols-[280px_1fr] mb-6 items-stretch">
                {stats.inventoryHealth !== null && (
                    <div className="bg-admin-text rounded-admin-card p-4 flex flex-col">
                        <div className="flex items-center justify-between mb-3">
                            <h2 className="text-sm font-semibold text-white">Inventory health</h2>
                            <AdminBadge status={health.status}>{health.label}</AdminBadge>
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="text-center">
                                <div className="text-admin-lg font-bold text-white">{stats.inventoryHealth.total_skus}</div>
                                <div className="text-xs text-white/70">Total SKUs</div>
                            </div>
                            <div className="text-center">
                                <div className="text-admin-lg font-bold text-admin-yellow">{stats.inventoryHealth.low_stock_count}</div>
                                <div className="text-xs text-white/70">Low stock</div>
                            </div>
                            <div className="text-center col-span-2">
                                <div className="text-admin-lg font-bold text-admin-red">{stats.inventoryHealth.out_of_stock_count}</div>
                                <div className="text-xs text-white/70">Out of stock</div>
                            </div>
                        </div>
                        <button
                            type="button"
                            onClick={() => router.visit('/admin/inventory/stock/low')}
                            className="text-xs font-semibold text-admin-blue mt-3"
                        >
                            View low-stock items →
                        </button>
                    </div>
                )}

                <div className="flex flex-col gap-3">
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        {quickActions.canCreateRepair && (
                            <button
                                type="button"
                                onClick={() => router.visit('/admin/repairs/create')}
                                className="flex items-center gap-3 bg-admin-surface border border-admin-border rounded-admin-card p-4 text-left hover:border-admin-blue transition-colors"
                            >
                                <div className="relative w-9 h-9 rounded-full bg-admin-blue-soft text-admin-blue flex items-center justify-center shrink-0">
                                    <Icon name="tools" />
                                    <span className="absolute -bottom-1 -right-1 w-4 h-4 rounded-full bg-admin-blue text-white flex items-center justify-center text-[9px]">
                                        <Icon name="plus-lg" />
                                    </span>
                                </div>
                                <span className="text-sm font-semibold text-admin-text">New repair</span>
                            </button>
                        )}
                        {quickActions.canCreateSale && (
                            <button
                                type="button"
                                onClick={() => router.visit('/admin/sales/checkout')}
                                className="flex items-center gap-3 bg-admin-surface border border-admin-border rounded-admin-card p-4 text-left hover:border-admin-blue transition-colors"
                            >
                                <div className="relative w-9 h-9 rounded-full bg-admin-green-soft text-admin-green flex items-center justify-center shrink-0">
                                    <Icon name="receipt" />
                                    <span className="absolute -bottom-1 -right-1 w-4 h-4 rounded-full bg-admin-green text-white flex items-center justify-center text-[9px]">
                                        <Icon name="plus-lg" />
                                    </span>
                                </div>
                                <span className="text-sm font-semibold text-admin-text">New sale</span>
                            </button>
                        )}
                        {quickActions.canViewShops && (
                            <button
                                type="button"
                                onClick={() => router.visit(quickActions.activeShopId ? `/admin/shops/${quickActions.activeShopId}` : '/admin/shops')}
                                className="flex items-center gap-3 bg-admin-surface border border-admin-border rounded-admin-card p-4 text-left hover:border-admin-blue transition-colors"
                            >
                                <div className="relative w-9 h-9 rounded-full bg-admin-yellow-soft text-admin-yellow-text flex items-center justify-center shrink-0">
                                    <Icon name="shop" />
                                    <span className="absolute -bottom-1 -right-1 w-4 h-4 rounded-full bg-admin-yellow-text text-white flex items-center justify-center text-[9px]">
                                        <Icon name="eye" />
                                    </span>
                                </div>
                                <span className="text-sm font-semibold text-admin-text">View shop</span>
                            </button>
                        )}
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        {stats.openRepairs !== null && (
                            <AdminStatCard label="Open repairs" value={stats.openRepairs.toString()} variant="blue" fillHeight />
                        )}
                        {stats.todaysSales !== null && (
                            <AdminStatCard
                                label="Today's sales"
                                value={formatNaira(stats.todaysSales.total_minor)}
                                delta={`${stats.todaysSales.sales_count} sales`}
                                variant="blue"
                                fillHeight
                            />
                        )}
                        {stats.pendingDisputes !== null && (
                            <AdminStatCard label="Pending disputes" value={stats.pendingDisputes.toString()} variant="blue" fillHeight />
                        )}
                    </div>
                </div>
            </div>

            {shopAnalytics && (
                <div className="grid gap-4 lg:grid-cols-2 mb-6 items-stretch">
                    <div className="bg-admin-surface border border-admin-border rounded-admin-card p-5">
                        <h2 className="text-sm font-semibold text-admin-text mb-3">Revenue (last 14 days)</h2>
                        <RevenueChart series={shopAnalytics.revenue_series} />
                    </div>
                    <div className="bg-admin-surface border border-admin-border rounded-admin-card p-5">
                        <h2 className="text-sm font-semibold text-admin-text mb-3">Revenue by stream</h2>
                        <RevenueBreakdownChart breakdown={shopAnalytics.revenue_breakdown} />
                    </div>
                </div>
            )}

            <div className="grid gap-4 lg:grid-cols-2 mb-6">
                <div className="bg-admin-surface border border-admin-border rounded-admin-card p-5">
                    <div className="flex items-center justify-between mb-3">
                        <h2 className="text-sm font-semibold text-admin-text">Recent repairs</h2>
                        <button type="button" onClick={() => router.visit('/admin/repairs')} className="text-xs font-semibold text-admin-blue">
                            View all
                        </button>
                    </div>
                    {recentRepairs.length === 0 ? (
                        <AdminEmptyState icon="tools" title="No repairs yet" />
                    ) : (
                        <ul className="divide-y divide-admin-border">
                            {recentRepairs.map((repair) => (
                                <li
                                    key={repair.id}
                                    className="flex items-center justify-between gap-2 py-2.5 cursor-pointer"
                                    onClick={() => router.visit(`/admin/repairs/${repair.id}`)}
                                >
                                    <span className="text-sm text-admin-text truncate">
                                        {repair.device_make} {repair.device_model}
                                    </span>
                                    <AdminBadge status={REPAIR_STATUS_BADGE[repair.repair_status] ?? 'neutral'}>
                                        {repair.repair_status.replace(/_/g, ' ')}
                                    </AdminBadge>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>

                <div className="bg-admin-surface border border-admin-border rounded-admin-card p-5">
                    <div className="flex items-center justify-between mb-3">
                        <h2 className="text-sm font-semibold text-admin-text">Recent sales</h2>
                        <button type="button" onClick={() => router.visit('/admin/sales')} className="text-xs font-semibold text-admin-blue">
                            View all
                        </button>
                    </div>
                    {recentSales.length === 0 ? (
                        <AdminEmptyState icon="receipt" title="No sales yet" />
                    ) : (
                        <ul className="divide-y divide-admin-border">
                            {recentSales.map((sale) => (
                                <li
                                    key={sale.id}
                                    className="flex items-center justify-between gap-2 py-2.5 cursor-pointer"
                                    onClick={() => router.visit(`/admin/sales/${sale.id}`)}
                                >
                                    <span className="text-sm text-admin-text truncate">{sale.invoice_number}</span>
                                    <span className="text-sm font-mono text-admin-text">{formatNaira(sale.total_minor)}</span>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </div>

            <div className="bg-admin-surface border border-admin-border rounded-admin-card p-5">
                <h2 className="text-sm font-semibold text-admin-text mb-3">Recent login activity</h2>
                {loginActivity.length === 0 ? (
                    <AdminEmptyState icon="box-arrow-in-right" title="No login activity yet" />
                ) : (
                    <ul className="divide-y divide-admin-border">
                        {loginActivity.map((event) => (
                            <li key={event.id} className="flex items-center justify-between gap-2 py-2">
                                <span className="text-sm text-admin-text">{event.actor_name ?? 'Staff member'}</span>
                                <span className="text-xs text-admin-text3">{new Date(event.created_at).toLocaleString()}</span>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </AdminShell>
    );
}
