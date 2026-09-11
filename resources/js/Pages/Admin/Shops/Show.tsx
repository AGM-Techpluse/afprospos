import { Head, router } from '@inertiajs/react';
import AdminShell from '../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../Components/Admin/AdminPageHead';
import AdminBadge from '../../../Components/Admin/AdminBadge';
import AdminButton from '../../../Components/Admin/AdminButton';
import AdminStatCard from '../../../Components/Admin/AdminStatCard';
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
                <div className="h-full bg-admin-surface border border-admin-border rounded-admin-card p-6">
                    <dl className="space-y-3 text-sm">
                        <div className="flex justify-between">
                            <dt className="text-admin-text2">SKU prefix code</dt>
                            <dd className="text-admin-text">{shop.sku_prefix_code}</dd>
                        </div>
                        <div className="flex justify-between">
                            <dt className="text-admin-text2">Address</dt>
                            <dd className="text-admin-text text-right">{shop.address}</dd>
                        </div>
                        <div className="flex justify-between">
                            <dt className="text-admin-text2">Contact phone</dt>
                            <dd className="text-admin-text">{shop.contact_phone}</dd>
                        </div>
                        <div className="flex justify-between">
                            <dt className="text-admin-text2">Contact email</dt>
                            <dd className="text-admin-text">{shop.contact_email}</dd>
                        </div>
                        <div className="flex justify-between">
                            <dt className="text-admin-text2">Status</dt>
                            <dd>
                                <AdminBadge status={shop.status === 'active' ? 'success' : 'neutral'}>
                                    {shop.status === 'active' ? 'Active' : 'Inactive'}
                                </AdminBadge>
                            </dd>
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
