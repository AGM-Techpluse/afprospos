import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminShell from '../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../Components/Admin/AdminPageHead';
import AdminBadge from '../../../../Components/Admin/AdminBadge';
import AdminButton from '../../../../Components/Admin/AdminButton';
import AdminEmptyState from '../../../../Components/Admin/AdminEmptyState';
import ResponsiveDataTable, { DataTableColumn } from '../../../../Components/Tables/ResponsiveDataTable';

type LowStockItem = {
    stock_level_id: number | null;
    sku_id: number;
    sku_code: string;
    brand: string;
    model: string;
    shop_id: number;
    on_hand: number;
    reserved: number;
    available: number;
    low_stock_threshold: number;
    is_serialized: boolean;
};

type Shop = { id: number; name: string };

interface LowStockProps {
    items: LowStockItem[];
    filters: { shop_id: number | null };
    shops: Shop[];
}

export default function LowStock({ items, filters, shops }: LowStockProps) {
    const [loading, setLoading] = useState(false);
    const shopName = (id: number) => shops.find((shop) => shop.id === id)?.name ?? 'Unknown shop';

    const columns: DataTableColumn<LowStockItem>[] = [
        {
            key: 'sku_code',
            label: 'SKU',
            render: (item) => (
                <>
                    {item.sku_code} — {item.brand} {item.model}
                    {item.is_serialized && <span className="ml-1.5 text-admin-text3 text-xs">(serialized)</span>}
                </>
            ),
        },
        { key: 'shop_id', label: 'Shop', render: (item) => shopName(item.shop_id) },
        {
            key: 'available',
            label: 'Available',
            render: (item) => <AdminBadge status={item.available <= 0 ? 'danger' : 'warning'}>{item.available}</AdminBadge>,
        },
        { key: 'low_stock_threshold', label: 'Threshold' },
    ];

    function filterByShop(shopId: number | null) {
        router.get(
            '/admin/inventory/stock/low',
            { shop_id: shopId ?? '' },
            { preserveState: true, preserveScroll: true, replace: true, showProgress: false, onStart: () => setLoading(true), onFinish: () => setLoading(false) },
        );
    }

    return (
        <AdminShell>
            <Head title="Low stock" />

            <AdminPageHead
                title="Low stock"
                description="SKUs whose Available quantity has fallen to or below their configured threshold (INV-BR-07)."
                backHref="/admin/inventory/stock"
                backLabel="Back to stock"
            />

            <ResponsiveDataTable
                columns={columns}
                rows={items}
                getRowKey={(item) => item.stock_level_id ?? `${item.sku_id}-${item.shop_id}`}
                mobilePrimaryField="sku_code"
                mobileStatusField="available"
                mobileDetailFields={['shop_id', 'low_stock_threshold']}
                loading={loading}
                toolbar={
                    <select
                        value={filters.shop_id ?? ''}
                        onChange={(e) => filterByShop(e.target.value ? Number(e.target.value) : null)}
                        className="border border-admin-border rounded-admin-button px-2.5 py-1.5 text-xs bg-admin-bg outline-none focus:border-admin-blue focus:ring-2 focus:ring-admin-focus"
                    >
                        <option value="">All shops</option>
                        {shops.map((shop) => (
                            <option key={shop.id} value={shop.id}>
                                {shop.name}
                            </option>
                        ))}
                    </select>
                }
                renderActions={(item) =>
                    item.is_serialized ? (
                        <AdminButton variant="neutral" onClick={() => router.visit(`/admin/inventory/products/${item.sku_id}`)}>
                            View product
                        </AdminButton>
                    ) : (
                        <AdminButton variant="neutral" onClick={() => router.visit(`/admin/inventory/stock/${item.sku_id}/${item.shop_id}/adjust`)}>
                            Receive / Adjust
                        </AdminButton>
                    )
                }
                emptyState={
                    <AdminEmptyState icon="check-circle" title="Nothing low on stock" description="Every SKU is above its configured threshold." />
                }
            />
        </AdminShell>
    );
}
