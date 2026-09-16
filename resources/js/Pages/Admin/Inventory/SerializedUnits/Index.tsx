import { useEffect, useRef, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminShell from '../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../Components/Admin/AdminPageHead';
import AdminBadge, { AdminBadgeStatus } from '../../../../Components/Admin/AdminBadge';
import AdminButton from '../../../../Components/Admin/AdminButton';
import AdminEmptyState from '../../../../Components/Admin/AdminEmptyState';
import AdminPagination from '../../../../Components/Admin/AdminPagination';
import InventorySubNav from '../../../../Components/Admin/InventorySubNav';
import Icon from '../../../../Components/Icons/Icon';
import ResponsiveDataTable, { DataTableColumn } from '../../../../Components/Tables/ResponsiveDataTable';

type UnitRow = {
    id: number;
    imei: string;
    sku_id: number;
    sku_code: string;
    brand: string;
    model: string;
    shop_id: number;
    condition: string;
    status: string;
};

type Shop = { id: number; name: string };

type Paginated<T> = { data: T[]; current_page: number; last_page: number; per_page: number; total: number };

interface SerializedUnitsIndexProps {
    units: Paginated<UnitRow>;
    filters: { search: string; shop_id: number | null; status: string };
    shops: Shop[];
}

const STATUS_BADGE: Record<string, AdminBadgeStatus> = {
    available: 'success',
    reserved: 'warning',
    transferring: 'info',
    sold: 'neutral',
};

export default function SerializedUnitsIndex({ units, filters, shops }: SerializedUnitsIndexProps) {
    const [search, setSearch] = useState(filters.search);
    const [shopId, setShopId] = useState(filters.shop_id);
    const [status, setStatus] = useState(filters.status);
    const [loading, setLoading] = useState(false);
    const skipNextFetch = useRef(true);

    const shopName = (id: number) => shops.find((shop) => shop.id === id)?.name ?? 'Unknown shop';

    const columns: DataTableColumn<UnitRow>[] = [
        { key: 'imei', label: 'IMEI / serial' },
        { key: 'model', label: 'Product', render: (row) => `${row.sku_code} — ${row.brand} ${row.model}` },
        { key: 'shop_id', label: 'Shop', render: (row) => shopName(row.shop_id) },
        { key: 'condition', label: 'Condition', render: (row) => <span className="capitalize">{row.condition}</span> },
        { key: 'status', label: 'Status', render: (row) => <AdminBadge status={STATUS_BADGE[row.status] ?? 'neutral'}>{row.status}</AdminBadge> },
    ];

    useEffect(() => {
        if (skipNextFetch.current) {
            skipNextFetch.current = false;
            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                '/admin/inventory/serialized-units',
                { search: search || undefined, shop_id: shopId ?? '', status: status || undefined },
                { only: ['units'], preserveState: true, preserveScroll: true, replace: true, showProgress: false, onStart: () => setLoading(true), onFinish: () => setLoading(false) },
            );
        }, 350);

        return () => clearTimeout(timeout);
    }, [search, shopId, status]);

    function goToPage(page: number) {
        router.get(
            '/admin/inventory/serialized-units',
            { search: search || undefined, shop_id: shopId ?? '', status: status || undefined, page },
            { only: ['units'], preserveState: true, preserveScroll: true, replace: true, showProgress: false, onStart: () => setLoading(true), onFinish: () => setLoading(false) },
        );
    }

    return (
        <AdminShell>
            <Head title="Serialized units" />

            <AdminPageHead title="Inventory" description="Individually IMEI/serial-tracked units across shops." />

            <InventorySubNav />

            <ResponsiveDataTable
                columns={columns}
                rows={units.data}
                getRowKey={(row) => row.id}
                mobilePrimaryField="model"
                mobileStatusField="status"
                mobileDetailFields={['imei', 'shop_id', 'condition']}
                loading={loading}
                toolbar={
                    <>
                        <div className="relative flex-1 min-w-[160px] max-w-[260px]">
                            <Icon name="search" className="absolute left-2.5 top-1/2 -translate-y-1/2 text-admin-text3 text-xs" />
                            <input
                                type="text"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Filter by IMEI, SKU, brand, model…"
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
                        <select
                            value={status}
                            onChange={(e) => setStatus(e.target.value)}
                            className="border border-admin-border rounded-admin-button text-xs py-1.5 px-2 bg-admin-bg"
                        >
                            <option value="">All statuses</option>
                            <option value="available">Available</option>
                            <option value="reserved">Reserved</option>
                            <option value="transferring">Transferring</option>
                            <option value="sold">Sold</option>
                        </select>
                    </>
                }
                renderActions={(row) => (
                    <AdminButton variant="neutral" onClick={() => router.visit(`/admin/inventory/products/${row.sku_id}`)}>
                        View product
                    </AdminButton>
                )}
                emptyState={
                    <AdminEmptyState
                        icon="upc-scan"
                        title="No serialized units found"
                        description="IMEI/serial-tracked units you receive will show up here."
                    />
                }
                footer={
                    units.total > 0 ? (
                        <AdminPagination
                            currentPage={units.current_page}
                            totalItems={units.total}
                            pageSize={units.per_page}
                            onPageChange={goToPage}
                        />
                    ) : undefined
                }
            />
        </AdminShell>
    );
}
