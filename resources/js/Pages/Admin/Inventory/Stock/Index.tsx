import { FormEvent, useEffect, useRef, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminShell from '../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../Components/Admin/AdminPageHead';
import AdminButton from '../../../../Components/Admin/AdminButton';
import AdminBulkActionBar from '../../../../Components/Admin/AdminBulkActionBar';
import AdminModal from '../../../../Components/Admin/AdminModal';
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

type BulkMode = 'adjust' | 'transfer' | null;

export default function StockIndex({ stock, filters, shops }: StockIndexProps) {
    const [search, setSearch] = useState(filters.search);
    const [shopId, setShopId] = useState(filters.shop_id);
    const [loading, setLoading] = useState(false);
    const skipNextFetch = useRef(true);

    const [selectedIds, setSelectedIds] = useState<(string | number)[]>([]);
    const [bulkMode, setBulkMode] = useState<BulkMode>(null);
    const [bulkProcessing, setBulkProcessing] = useState(false);
    const [reason, setReason] = useState('');
    const [deltas, setDeltas] = useState<Record<number, string>>({});
    const [toShopId, setToShopId] = useState('');
    const [quantities, setQuantities] = useState<Record<number, string>>({});

    const shopName = (id: number) => shops.find((shop) => shop.id === id)?.name ?? 'Unknown shop';
    const selectedRows = stock.data.filter((row) => selectedIds.includes(row.id));

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

    function openBulkAdjust() {
        setDeltas({});
        setReason('');
        setBulkMode('adjust');
    }

    function openBulkTransfer() {
        const defaults: Record<number, string> = {};
        selectedRows.forEach((row) => { defaults[row.id] = String(row.available); });
        setQuantities(defaults);
        setToShopId('');
        setBulkMode('transfer');
    }

    function closeBulk() {
        setBulkMode(null);
    }

    function clearSelection() {
        setSelectedIds([]);
        setBulkMode(null);
    }

    function submitBulkAdjust(e: FormEvent) {
        e.preventDefault();
        setBulkProcessing(true);
        router.post(
            '/admin/inventory/stock/bulk-adjust',
            {
                reason,
                items: selectedRows.map((row) => ({
                    sku_id: row.sku_id,
                    shop_id: row.shop_id,
                    delta: parseInt(deltas[row.id] || '0', 10),
                })),
            },
            { onFinish: () => setBulkProcessing(false), onSuccess: () => clearSelection() },
        );
    }

    function submitBulkTransfer(e: FormEvent) {
        e.preventDefault();
        setBulkProcessing(true);
        router.post(
            '/admin/inventory/stock/bulk-transfer',
            {
                to_shop_id: toShopId,
                items: selectedRows.map((row) => ({
                    sku_id: row.sku_id,
                    from_shop_id: row.shop_id,
                    quantity: parseInt(quantities[row.id] || '0', 10),
                })),
            },
            { onFinish: () => setBulkProcessing(false), onSuccess: () => clearSelection() },
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

            <AdminBulkActionBar
                selectedCount={selectedIds.length}
                onClear={clearSelection}
                actions={
                    <>
                        <AdminButton type="button" variant="neutral" onClick={openBulkAdjust}>
                            Adjust stock
                        </AdminButton>
                        <AdminButton type="button" variant="neutral" onClick={openBulkTransfer}>
                            Transfer to shop
                        </AdminButton>
                    </>
                }
            />

            <AdminModal
                open={bulkMode === 'adjust'}
                onClose={closeBulk}
                title="Adjust stock"
                description={`${selectedRows.length} item${selectedRows.length === 1 ? '' : 's'} selected`}
                dismissible={!bulkProcessing}
                footer={
                    <div className="flex items-center gap-2">
                        <AdminButton type="submit" form="bulk-adjust-form" isLoading={bulkProcessing}>Apply</AdminButton>
                        <AdminButton type="button" variant="neutral" onClick={closeBulk}>Cancel</AdminButton>
                    </div>
                }
            >
                <form id="bulk-adjust-form" onSubmit={submitBulkAdjust} className="pb-6">
                    <div className="flex flex-col gap-2 mb-3">
                        {selectedRows.map((row) => (
                            <div key={row.id} className="flex items-center justify-between gap-3">
                                <span className="text-sm text-admin-text truncate">
                                    {row.brand} {row.model} · {shopName(row.shop_id)} <span className="text-admin-text3">({row.on_hand} on hand)</span>
                                </span>
                                <input
                                    type="number"
                                    value={deltas[row.id] ?? ''}
                                    onChange={(e) => setDeltas((current) => ({ ...current, [row.id]: e.target.value }))}
                                    placeholder="+/- qty"
                                    className="w-28 border border-admin-border-strong rounded-admin-button px-2.5 py-1.5 text-xs bg-white outline-none focus:border-admin-blue focus:ring-2 focus:ring-admin-focus"
                                    required
                                />
                            </div>
                        ))}
                    </div>
                    <input
                        type="text"
                        value={reason}
                        onChange={(e) => setReason(e.target.value)}
                        placeholder="Reason for this batch (e.g. Weekly stock count)"
                        className="w-full border border-admin-border-strong rounded-admin-button px-2.5 py-1.5 text-xs bg-white outline-none focus:border-admin-blue focus:ring-2 focus:ring-admin-focus"
                        required
                    />
                </form>
            </AdminModal>

            <AdminModal
                open={bulkMode === 'transfer'}
                onClose={closeBulk}
                title="Transfer to shop"
                description={`${selectedRows.length} item${selectedRows.length === 1 ? '' : 's'} selected`}
                dismissible={!bulkProcessing}
                footer={
                    <div className="flex items-center gap-2">
                        <AdminButton type="submit" form="bulk-transfer-form" isLoading={bulkProcessing}>Transfer</AdminButton>
                        <AdminButton type="button" variant="neutral" onClick={closeBulk}>Cancel</AdminButton>
                    </div>
                }
            >
                <form id="bulk-transfer-form" onSubmit={submitBulkTransfer} className="pb-6">
                    <select
                        value={toShopId}
                        onChange={(e) => setToShopId(e.target.value)}
                        className="w-full border border-admin-border-strong rounded-admin-button px-2.5 py-1.5 text-xs bg-white outline-none focus:border-admin-blue focus:ring-2 focus:ring-admin-focus mb-3"
                        required
                    >
                        <option value="">Select destination shop…</option>
                        {shops.map((shop) => (
                            <option key={shop.id} value={shop.id}>{shop.name}</option>
                        ))}
                    </select>
                    <div className="flex flex-col gap-2 mb-3">
                        {selectedRows.map((row) => (
                            <div key={row.id} className="flex items-center justify-between gap-3">
                                <span className="text-sm text-admin-text truncate">
                                    {row.brand} {row.model} · from {shopName(row.shop_id)} <span className="text-admin-text3">({row.available} available)</span>
                                </span>
                                <input
                                    type="number"
                                    min={1}
                                    max={row.available}
                                    value={quantities[row.id] ?? ''}
                                    onChange={(e) => setQuantities((current) => ({ ...current, [row.id]: e.target.value }))}
                                    className="w-24 border border-admin-border-strong rounded-admin-button px-2.5 py-1.5 text-xs bg-white outline-none focus:border-admin-blue focus:ring-2 focus:ring-admin-focus"
                                    required
                                />
                            </div>
                        ))}
                    </div>
                </form>
            </AdminModal>

            <ResponsiveDataTable
                columns={columns}
                rows={stock.data}
                getRowKey={(row) => row.id}
                mobilePrimaryField="model"
                mobileStatusField="available"
                mobileDetailFields={['sku_code', 'brand', 'shop_id', 'on_hand', 'reserved']}
                loading={loading}
                selectable
                selectedIds={selectedIds}
                onSelectionChange={setSelectedIds}
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
