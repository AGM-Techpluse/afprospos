import { Head, router } from '@inertiajs/react';
import AdminShell from '../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../Components/Admin/AdminPageHead';
import AdminBadge from '../../../Components/Admin/AdminBadge';
import AdminButton from '../../../Components/Admin/AdminButton';
import AdminStatCard from '../../../Components/Admin/AdminStatCard';
import Icon from '../../../Components/Icons/Icon';
import { initialsFor } from '../../../lib/initials';
import RevenueChart, { RevenuePoint } from '../../../Features/Shop/RevenueChart';
import { formatNaira } from '../../../lib/money';

type Shop = {
    id: number;
    name: string;
    sku_prefix_code: string;
    address: string;
    contact_phone: string;
    contact_email: string;
    status: 'active' | 'inactive';
};

type ShopAnalytics = {
    staff_count: number;
    stock_units: number;
    sales_count: number;
    revenue_minor: number;
    revenue_series: RevenuePoint[];
};

export default function ShopShow({ shop, analytics }: { shop: Shop; analytics: ShopAnalytics }) {
    return (
        <AdminShell>
            <Head title={shop.name} />

            <AdminPageHead
                title={shop.name}
                description={shop.sku_prefix_code}
                backHref="/admin/shops"
                backLabel="Back to shops"
                actions={
                    <AdminButton variant="neutral" onClick={() => router.visit(`/admin/shops/${shop.id}/edit`)}>
                        Edit
                    </AdminButton>
                }
            />

            <div className="grid gap-6 lg:grid-cols-[minmax(0,400px)_1fr] mb-6">
                <div className="h-full bg-admin-surface-dark border border-admin-surface-dark-border rounded-admin-card p-6 flex flex-col">
                    <div className="flex items-center gap-3.5 mb-6">
                        <div className="h-11 w-11 rounded-full bg-admin-surface-dark-border text-admin-surface-dark-text text-sm font-bold flex items-center justify-center shrink-0">
                            {initialsFor(shop.name)}
                        </div>
                        <div className="min-w-0">
                            <p className="text-admin-md font-semibold text-admin-surface-dark-text truncate">{shop.name}</p>
                            <p className="text-admin-xs text-admin-surface-dark-text2 truncate">{shop.sku_prefix_code}</p>
                        </div>
                        <div className="ml-auto shrink-0">
                            <AdminBadge status={shop.status === 'active' ? 'success' : 'neutral'}>
                                {shop.status === 'active' ? 'Active' : 'Inactive'}
                            </AdminBadge>
                        </div>
                    </div>

                    <dl className="space-y-4 text-sm">
                        <div className="flex items-start gap-3">
                            <Icon name="geo-alt" className="text-admin-surface-dark-text2 mt-0.5 shrink-0" />
                            <div className="min-w-0">
                                <dt className="text-xs text-admin-surface-dark-text2 mb-0.5">Address</dt>
                                <dd className="text-admin-surface-dark-text">{shop.address}</dd>
                            </div>
                        </div>
                        <div className="flex items-start gap-3">
                            <Icon name="telephone" className="text-admin-surface-dark-text2 mt-0.5 shrink-0" />
                            <div className="min-w-0">
                                <dt className="text-xs text-admin-surface-dark-text2 mb-0.5">Contact phone</dt>
                                <dd className="text-admin-surface-dark-text">{shop.contact_phone}</dd>
                            </div>
                        </div>
                        <div className="flex items-start gap-3">
                            <Icon name="envelope" className="text-admin-surface-dark-text2 mt-0.5 shrink-0" />
                            <div className="min-w-0">
                                <dt className="text-xs text-admin-surface-dark-text2 mb-0.5">Contact email</dt>
                                <dd className="text-admin-surface-dark-text truncate">{shop.contact_email}</dd>
                            </div>
                        </div>
                    </dl>
                </div>

                <div className="grid grid-cols-2 grid-rows-2 gap-3 sm:gap-4 h-full">
                    <AdminStatCard fillHeight variant="blue" label="Revenue" value={formatNaira(analytics.revenue_minor)} />
                    <AdminStatCard fillHeight variant="blue" label="Sales" value={analytics.sales_count.toString()} />
                    <AdminStatCard fillHeight variant="blue" label="Stock units" value={analytics.stock_units.toString()} />
                    <AdminStatCard
                        fillHeight
                        variant="blue"
                        label="Staff"
                        value={analytics.staff_count.toString()}
                        href={`/admin/staff?shop_id=${shop.id}`}
                    />
                </div>
            </div>

            <div className="bg-admin-surface border border-admin-border rounded-admin-card p-6">
                <h2 className="text-admin-md font-semibold text-admin-text mb-4">Revenue — last 14 days</h2>
                <RevenueChart series={analytics.revenue_series} />
            </div>
        </AdminShell>
    );
}
