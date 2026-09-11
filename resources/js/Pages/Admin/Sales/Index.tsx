import { useEffect, useRef, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminShell from '../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../Components/Admin/AdminPageHead';
import AdminButton from '../../../Components/Admin/AdminButton';
import AdminEmptyState from '../../../Components/Admin/AdminEmptyState';
import AdminPagination from '../../../Components/Admin/AdminPagination';
import Icon from '../../../Components/Icons/Icon';
import ResponsiveDataTable, { DataTableColumn } from '../../../Components/Tables/ResponsiveDataTable';
import { formatNaira } from '../../../lib/money';

type SaleRow = {
    id: number;
    shop_id: number;
    customer_id: number | null;
    total_minor: number;
    payment_method: string;
    invoice_number: string;
    created_at: string;
};

type Paginated<T> = { data: T[]; current_page: number; last_page: number; per_page: number; total: number };

interface SalesIndexProps {
    sales: Paginated<SaleRow>;
    filters: { search: string; shop_id: number | null };
}

const COLUMNS: DataTableColumn<SaleRow>[] = [
    { key: 'invoice_number', label: 'Invoice' },
    { key: 'created_at', label: 'Date', render: (row) => new Date(row.created_at).toLocaleString() },
    { key: 'customer_id', label: 'Customer', render: (row) => (row.customer_id ? `#${row.customer_id}` : 'Walk-in') },
    { key: 'payment_method', label: 'Payment' },
    { key: 'total_minor', label: 'Total', render: (row) => formatNaira(row.total_minor) },
];

export default function SalesIndex({ sales, filters }: SalesIndexProps) {
    const [search, setSearch] = useState(filters.search);
    const skipNextFetch = useRef(true);

    useEffect(() => {
        if (skipNextFetch.current) {
            skipNextFetch.current = false;
            return;
        }

        const timeout = setTimeout(() => {
            router.get('/admin/sales', { search: search || undefined }, { preserveState: true, preserveScroll: true, replace: true });
        }, 350);

        return () => clearTimeout(timeout);
    }, [search]);

    function goToPage(page: number) {
        router.get('/admin/sales', { search: search || undefined, page }, { preserveState: true, preserveScroll: true, replace: true });
    }

    return (
        <AdminShell>
            <Head title="Sales" />

            <AdminPageHead
                title="Sales"
                description="Completed sales and invoices."
                actions={
                    <AdminButton onClick={() => router.visit('/admin/sales/checkout')}>
                        <Icon name="plus-lg" className="mr-1.5" />
                        New sale
                    </AdminButton>
                }
            />

            <ResponsiveDataTable
                columns={COLUMNS}
                rows={sales.data}
                getRowKey={(row) => row.id}
                mobilePrimaryField="invoice_number"
                mobileStatusField="payment_method"
                mobileDetailFields={['created_at', 'customer_id', 'total_minor']}
                toolbar={
                    <div className="relative flex-1 min-w-[160px] max-w-[260px]">
                        <Icon name="search" className="absolute left-2.5 top-1/2 -translate-y-1/2 text-admin-text3 text-xs" />
                        <input
                            type="text"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Filter by invoice number…"
                            className="w-full border border-admin-border rounded-admin-button pl-8 pr-3 py-1.5 text-xs bg-admin-bg outline-none focus:border-admin-blue focus:ring-2 focus:ring-admin-focus"
                        />
                    </div>
                }
                renderActions={(row) => (
                    <AdminButton variant="neutral" onClick={() => router.visit(`/admin/sales/${row.id}`)}>
                        View
                    </AdminButton>
                )}
                emptyState={
                    <AdminEmptyState icon="receipt" title="No sales yet" description="Completed sales will show up here." />
                }
                footer={
                    sales.total > 0 ? (
                        <AdminPagination currentPage={sales.current_page} totalItems={sales.total} pageSize={sales.per_page} onPageChange={goToPage} />
                    ) : undefined
                }
            />
        </AdminShell>
    );
}
