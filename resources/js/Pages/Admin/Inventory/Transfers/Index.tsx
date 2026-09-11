import { useEffect, useRef, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminShell from '../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../Components/Admin/AdminPageHead';
import AdminBadge from '../../../../Components/Admin/AdminBadge';
import AdminButton from '../../../../Components/Admin/AdminButton';
import AdminEmptyState from '../../../../Components/Admin/AdminEmptyState';
import AdminPagination from '../../../../Components/Admin/AdminPagination';
import InventorySubNav from '../../../../Components/Admin/InventorySubNav';
import ResponsiveDataTable, { DataTableColumn } from '../../../../Components/Tables/ResponsiveDataTable';

type TransferRow = {
    id: number;
    sku_code: string;
    brand: string;
    model: string;
    inventory_item_id: number | null;
    quantity: number | null;
    from_shop_id: number;
    to_shop_id: number;
    status: 'in_transit' | 'completed' | 'cancelled';
    created_at: string;
};

type Paginated<T> = { data: T[]; current_page: number; last_page: number; per_page: number; total: number };

interface TransfersIndexProps {
    transfers: Paginated<TransferRow>;
    filters: { status: string; shop_id: number | null };
}

const COLUMNS: DataTableColumn<TransferRow>[] = [
    { key: 'sku_code', label: 'SKU' },
    { key: 'model', label: 'Product', render: (row) => `${row.brand} ${row.model}` },
    { key: 'quantity', label: 'Qty/Unit', render: (row) => (row.inventory_item_id ? '1 unit' : row.quantity) },
    { key: 'from_shop_id', label: 'From', render: (row) => `#${row.from_shop_id}` },
    { key: 'to_shop_id', label: 'To', render: (row) => `#${row.to_shop_id}` },
    {
        key: 'status',
        label: 'Status',
        render: (row) => (
            <AdminBadge status={row.status === 'completed' ? 'success' : row.status === 'cancelled' ? 'neutral' : 'info'}>
                {row.status === 'in_transit' ? 'In transit' : row.status === 'completed' ? 'Completed' : 'Cancelled'}
            </AdminBadge>
        ),
    },
];

export default function TransfersIndex({ transfers, filters }: TransfersIndexProps) {
    const [status, setStatus] = useState(filters.status);
    const skipNextFetch = useRef(true);

    useEffect(() => {
        if (skipNextFetch.current) {
            skipNextFetch.current = false;
            return;
        }

        const timeout = setTimeout(() => {
            router.get('/admin/inventory/transfers', { status: status || undefined }, { preserveState: true, preserveScroll: true, replace: true });
        }, 200);

        return () => clearTimeout(timeout);
    }, [status]);

    function goToPage(page: number) {
        router.get('/admin/inventory/transfers', { status: status || undefined, page }, { preserveState: true, preserveScroll: true, replace: true });
    }

    return (
        <AdminShell>
            <Head title="Transfers" />

            <AdminPageHead
                title="Inventory"
                description="Inter-shop transfer lifecycle."
                actions={<AdminButton onClick={() => router.visit('/admin/inventory/transfers/create')}>New transfer</AdminButton>}
            />

            <InventorySubNav />

            <ResponsiveDataTable
                columns={COLUMNS}
                rows={transfers.data}
                getRowKey={(row) => row.id}
                mobilePrimaryField="model"
                mobileStatusField="status"
                mobileDetailFields={['sku_code', 'from_shop_id', 'to_shop_id']}
                toolbar={
                    <select
                        value={status}
                        onChange={(e) => setStatus(e.target.value)}
                        className="border border-admin-border rounded-admin-button text-xs py-1.5 px-2 bg-admin-bg"
                    >
                        <option value="">All statuses</option>
                        <option value="in_transit">In transit</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                }
                renderActions={(row) => (
                    <AdminButton variant="neutral" onClick={() => router.visit(`/admin/inventory/transfers/${row.id}`)}>
                        View
                    </AdminButton>
                )}
                emptyState={<AdminEmptyState icon="arrow-left-right" title="No transfers found" description="Transfers you initiate will show up here." />}
                footer={
                    transfers.total > 0 ? (
                        <AdminPagination
                            currentPage={transfers.current_page}
                            totalItems={transfers.total}
                            pageSize={transfers.per_page}
                            onPageChange={goToPage}
                        />
                    ) : undefined
                }
            />
        </AdminShell>
    );
}
