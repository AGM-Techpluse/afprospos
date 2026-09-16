import { useEffect, useRef, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminShell from '../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../Components/Admin/AdminPageHead';
import AdminButton from '../../../../Components/Admin/AdminButton';
import AdminEmptyState from '../../../../Components/Admin/AdminEmptyState';
import AdminPagination from '../../../../Components/Admin/AdminPagination';
import InventorySubNav from '../../../../Components/Admin/InventorySubNav';
import Icon from '../../../../Components/Icons/Icon';
import ResponsiveDataTable, { DataTableColumn } from '../../../../Components/Tables/ResponsiveDataTable';

type StockRow = {
    id: number;
    sku_id: number;
    sku_code: string;
    brand: string;
    model: string;
    shop_id: number;
    on_hand: number;
    reserved: number;
    available: number;
};

type Shop = { id: number; name: string };

type Paginated<T> = { data: T[]; current_page: number; last_page: number; per_page: number; total: number };

interface StockIndexProps {
    stock: Paginated<StockRow>;
    filters: { search: string; shop_id: number | null };
    shops: Shop[];
}

export default function StockIndex({ stock, filters, shops }: StockIndexProps) {
    const [search, setSearch] = useState(filters.search);
    const [shopId, setShopId] = useState(filters.shop_id);
    const [loading, setLoading] = useState(false);
    const skipNextFetch = useRef(true);

    const shopName = (id: number) => shops.find((shop) => shop.id === id)?.name ?? 'Unknown shop';

    const columns: DataTableColumn<StockRow>[] = [
        { key: 'sku_code', label: 'SKU' },
        { key: 'brand', label: 'Brand' },
        { key: 'model', label: 'Model' },
        { key: 'shop_id', label: 'Shop', render: (row) => shopName(row.shop_id) },
        { key: 'on_hand', label: 'On hand' },
        { key: 'reserved', label: 'Reserved' },
        { key: 'available', label: 'Available' },
    ];

    useEffect(() => {
        if (skipNextFetch.current) {
            skipNextFetch.current = false;
            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                '/admin/inventory/stock',
                { search: search || undefined, shop_id: shopId ?? '' },
                { only: ['stock'], preserveState: true, preserveScroll: true, replace: true, showProgress: false, onStart: () => setLoading(true), onFinish: () => setLoading(false) },
            );
        }, 350);

        return () => clearTimeout(timeout);
    }, [search, shopId]);

    function goToPage(page: number) {
        router.get(
            '/admin/inventory/stock',
            { search: search || undefined, shop_id: shopId ?? '', page },
            { only: ['stock'], preserveState: true, preserveScroll: true, replace: true, showProgress: false, onStart: () => setLoading(true), onFinish: () => setLoading(false) },
        );
    }

    return (
        <AdminShell>
            <Head title="Stock" />

            <AdminPageHead
                title="Inventory"
                description="Non-serialized stock levels across shops."
                actions={<AdminButton variant="neutral" onClick={() => router.visit('/admin/inventory/stock/low')}>Low stock</AdminButton>}
            />

            <InventorySubNav />

            <ResponsiveDataTable
                columns={columns}
                rows={stock.data}
                getRowKey={(row) => row.id}
                mobilePrimaryField="model"
                mobileStatusField="available"
                mobileDetailFields={['sku_code', 'brand', 'shop_id', 'on_hand', 'reserved']}
                loading={loading}
                toolbar={
                    <>
                    <div className="relative flex-1 min-w-[160px] max-w-[260px]">
                        <Icon name="search" className="absolute left-2.5 top-1/2 -translate-y-1/2 text-admin-text3 text-xs" />
                        <input
                            type="text"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Filter by SKU, brand, model…"
                            className="w-full border border-admin-border rounded-admin-button pl-8 pr-3 py-1.5 text-xs bg-admin-bg outline-none focus:border-admin-blue focus:ring-2 focus:ring-admin-focus"
                        />
                    </div>
                    <select
                        value={shopId ?? ''}
                        onChange={(e) => setShopId(e.target.value ? Number(e.target.value) : null)}
                        className="border border-admin-border rounded-admin-button text-xs py-1.5 px-2 bg-admin-bg"
                    >
                        <option value="">All shops</option>
                        {shops.map((shop) => (
                            <option key={shop.id} value={shop.id}>
                                {shop.name}
                            </option>
                        ))}
                    </select>
                    </>
                }
                renderActions={(row) => (
                    <AdminButton
                        variant="neutral"
                        onClick={() => router.visit(`/admin/inventory/stock/${row.sku_id}/${row.shop_id}/adjust`)}
                    >
                        Adjust
                    </AdminButton>
                )}
                emptyState={
                    <AdminEmptyState
                        icon="box-seam"
                        title="No stock found"
                        description="Non-serialized stock you receive will show up here."
                    />
                }
                footer={
                    stock.total > 0 ? (
                        <AdminPagination
                            currentPage={stock.current_page}
                            totalItems={stock.total}
                            pageSize={stock.per_page}
                            onPageChange={goToPage}
                        />
                    ) : undefined
                }
            />
        </AdminShell>
    );
}
