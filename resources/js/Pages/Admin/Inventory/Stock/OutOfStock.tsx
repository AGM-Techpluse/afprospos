import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminShell from '../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../Components/Admin/AdminPageHead';
import AdminBadge from '../../../../Components/Admin/AdminBadge';
import AdminButton from '../../../../Components/Admin/AdminButton';
import AdminEmptyState from '../../../../Components/Admin/AdminEmptyState';
import AdminSelect from '../../../../Components/Admin/Forms/AdminSelect';
import ResponsiveDataTable, { DataTableColumn } from '../../../../Components/Tables/ResponsiveDataTable';

type OutOfStockItem = {
    stock_level_id: number | null;
    sku_id: number;
    sku_code: string;
    brand: string;
    model: string;
    shop_id: number;
    on_hand: number;
    reserved: number;
    available: number;
    low_stock_threshold: number | null;
    is_serialized: boolean;
};

type Shop = { id: number; name: string };

interface OutOfStockProps {
    items: OutOfStockItem[];
    filters: { shop_id: number | null };
    shops: Shop[];
}

export default function OutOfStock({ items, filters, shops }: OutOfStockProps) {
    const [loading, setLoading] = useState(false);
    const shopName = (id: number) => shops.find((shop) => shop.id === id)?.name ?? 'Unknown shop';

    const columns: DataTableColumn<OutOfStockItem>[] = [
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
            render: (item) => <AdminBadge status="danger">{item.available}</AdminBadge>,
        },
        { key: 'low_stock_threshold', label: 'Threshold', render: (item) => item.low_stock_threshold ?? '—' },
    ];

    function filterByShop(shopId: number | null) {
        router.get(
            '/admin/inventory/stock/out-of-stock',
            { shop_id: shopId ?? '' },
            { preserveState: true, preserveScroll: true, replace: true, showProgress: false, onStart: () => setLoading(true), onFinish: () => setLoading(false) },
        );
    }

    return (
        <AdminShell>
            <Head title="Out of stock" />

            <AdminPageHead
                title="Out of stock"
                description="SKUs whose Available quantity has fallen to zero or below."
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
                    <AdminSelect aria-label="Filter by shop" value={filters.shop_id ?? ''} onChange={(e) => filterByShop(e.target.value ? Number(e.target.value) : null)} containerClassName="mb-0">
                        <option value="">All shops</option>
                        {shops.map((shop) => (
                            <option key={shop.id} value={shop.id}>
                                {shop.name}
                            </option>
                        ))}
                    </AdminSelect>
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
                    <AdminEmptyState icon="check-circle" title="Nothing out of stock" description="Every SKU has Available stock above zero." />
                }
            />
        </AdminShell>
    );
}
