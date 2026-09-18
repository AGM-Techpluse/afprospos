import { useEffect, useRef, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminShell from '../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../Components/Admin/AdminPageHead';
import WarrantySubNav from '../../../../Components/Admin/WarrantySubNav';
import AdminBadge, { AdminBadgeStatus } from '../../../../Components/Admin/AdminBadge';
import AdminButton from '../../../../Components/Admin/AdminButton';
import AdminEmptyState from '../../../../Components/Admin/AdminEmptyState';
import AdminPagination from '../../../../Components/Admin/AdminPagination';
import ResponsiveDataTable, { DataTableColumn } from '../../../../Components/Tables/ResponsiveDataTable';

interface ReturnRequestRow {
    id: number;
    sale_id: number;
    customer: { name: string; phone: string } | null;
    resolution_state: string;
    return_window_expires_at: string;
    created_at: string;
}

interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface ReturnRequestsIndexProps {
    returns: Paginated<ReturnRequestRow>;
    filters: { search: string; status: string };
}

const STATE_BADGE: Record<string, AdminBadgeStatus> = {
    requested: 'info',
    under_assessment: 'warning',
    approved: 'success',
    denied: 'danger',
    resolved: 'neutral',
};

const STATE_LABEL: Record<string, string> = {
    requested: 'Requested',
    under_assessment: 'Under assessment',
    approved: 'Approved',
    denied: 'Denied',
    resolved: 'Resolved',
};

const COLUMNS: DataTableColumn<ReturnRequestRow>[] = [
    { key: 'id', label: 'Return #' },
    { key: 'sale_id', label: 'Sale', render: (row) => `#${row.sale_id}` },
    { key: 'customer', label: 'Customer', render: (row) => row.customer?.name ?? '—' },
    {
        key: 'resolution_state',
        label: 'Status',
        render: (row) => (
            <AdminBadge status={STATE_BADGE[row.resolution_state] ?? 'neutral'}>
                {STATE_LABEL[row.resolution_state] ?? row.resolution_state}
            </AdminBadge>
        ),
    },
    { key: 'return_window_expires_at', label: 'Window expires', render: (row) => new Date(row.return_window_expires_at).toLocaleDateString() },
];

export default function ReturnRequestsIndex({ returns, filters }: ReturnRequestsIndexProps) {
    const [search, setSearch] = useState(filters.search);
    const [status, setStatus] = useState(filters.status);
    const skipNextFetch = useRef(true);
    const hasActiveFilters = Boolean(filters.search || filters.status);

    useEffect(() => {
        if (skipNextFetch.current) {
            skipNextFetch.current = false;
            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                '/admin/warranty/returns',
                { search: search || undefined, status: status || undefined },
                { preserveState: true, preserveScroll: true, replace: true },
            );
        }, 350);

        return () => clearTimeout(timeout);
    }, [search, status]);

    function goToPage(page: number) {
        router.get(
            '/admin/warranty/returns',
            { search: search || undefined, status: status || undefined, page },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function clearFilters() {
        skipNextFetch.current = true;
        setSearch('');
        setStatus('');
        router.get('/admin/warranty/returns', {}, { preserveState: true, preserveScroll: true, replace: true });
    }

    return (
        <AdminShell>
            <Head title="Returns" />

            <AdminPageHead
                title="Returns"
                description="Assess, approve/deny, and refund customer return requests."
            />

            <WarrantySubNav />

            <ResponsiveDataTable
                columns={COLUMNS}
                rows={returns.data}
                getRowKey={(row) => row.id}
                mobilePrimaryField="id"
                mobileStatusField="resolution_state"
                mobileDetailFields={['sale_id']}
                toolbar={
                    <>
                        <input
                            type="text"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Filter by customer name or phone…"
                            className="flex-1 min-w-[160px] max-w-[260px] border border-admin-border rounded-admin-button px-2.5 py-1.5 text-xs bg-admin-bg outline-none focus:border-admin-blue focus:ring-2 focus:ring-admin-focus"
                        />
                        <select
                            value={status}
                            onChange={(e) => setStatus(e.target.value)}
                            className="border border-admin-border rounded-admin-button text-xs py-1.5 px-2 bg-admin-bg"
                        >
                            <option value="">All statuses</option>
                            {Object.entries(STATE_LABEL).map(([value, label]) => (
                                <option key={value} value={value}>{label}</option>
                            ))}
                        </select>
                        <div className="flex-1" />
                        {hasActiveFilters && (
                            <AdminButton type="button" variant="warning" onClick={clearFilters}>
                                Clear filters
                            </AdminButton>
                        )}
                    </>
                }
                renderActions={(row) => (
                    <AdminButton variant="neutral" onClick={() => router.visit(`/admin/warranty/returns/${row.id}`)}>
                        View
                    </AdminButton>
                )}
                emptyState={
                    <AdminEmptyState
                        icon="arrow-counterclockwise"
                        title="No return requests found"
                        description={hasActiveFilters ? 'No returns match these filters.' : 'Returns customers request will show up here.'}
                    />
                }
                footer={
                    returns.total > 0 ? (
                        <AdminPagination
                            currentPage={returns.current_page}
                            totalItems={returns.total}
                            pageSize={returns.per_page}
                            onPageChange={goToPage}
                        />
                    ) : undefined
                }
            />
        </AdminShell>
    );
}
