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
import { formatNaira } from '../../../../lib/money';

interface TradeInRow {
    id: number;
    customer: { name: string; phone: string } | null;
    device_description: { make?: string; model?: string };
    assessed_value_minor: number | null;
    resolution_state: string;
    created_at: string;
}

interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface TradeInsIndexProps {
    tradeIns: Paginated<TradeInRow>;
    filters: { search: string; status: string };
}

const STATE_BADGE: Record<string, AdminBadgeStatus> = {
    submitted: 'info',
    assessed: 'warning',
    approved: 'success',
    rejected: 'danger',
    applied: 'neutral',
};

const STATE_LABEL: Record<string, string> = {
    submitted: 'Submitted',
    assessed: 'Assessed',
    approved: 'Approved',
    rejected: 'Rejected',
    applied: 'Applied',
};

const COLUMNS: DataTableColumn<TradeInRow>[] = [
    { key: 'id', label: 'Trade-in #' },
    { key: 'customer', label: 'Customer', render: (row) => row.customer?.name ?? '—' },
    { key: 'device_description', label: 'Device', render: (row) => `${row.device_description.make ?? ''} ${row.device_description.model ?? ''}`.trim() || '—' },
    { key: 'assessed_value_minor', label: 'Assessed value', render: (row) => (row.assessed_value_minor !== null ? formatNaira(row.assessed_value_minor) : '—') },
    {
        key: 'resolution_state',
        label: 'Status',
        render: (row) => (
            <AdminBadge status={STATE_BADGE[row.resolution_state] ?? 'neutral'}>
                {STATE_LABEL[row.resolution_state] ?? row.resolution_state}
            </AdminBadge>
        ),
    },
];

export default function TradeInsIndex({ tradeIns, filters }: TradeInsIndexProps) {
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
                '/admin/warranty/trade-ins',
                { search: search || undefined, status: status || undefined },
                { preserveState: true, preserveScroll: true, replace: true },
            );
        }, 350);

        return () => clearTimeout(timeout);
    }, [search, status]);

    function goToPage(page: number) {
        router.get(
            '/admin/warranty/trade-ins',
            { search: search || undefined, status: status || undefined, page },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function clearFilters() {
        skipNextFetch.current = true;
        setSearch('');
        setStatus('');
        router.get('/admin/warranty/trade-ins', {}, { preserveState: true, preserveScroll: true, replace: true });
    }

    return (
        <AdminShell>
            <Head title="Trade-ins" />

            <AdminPageHead
                title="Trade-ins"
                description="Record, assess, and approve customer device trade-ins."
                actions={<AdminButton onClick={() => router.visit('/admin/warranty/trade-ins/create')}>New trade-in</AdminButton>}
            />

            <WarrantySubNav />

            <ResponsiveDataTable
                columns={COLUMNS}
                rows={tradeIns.data}
                getRowKey={(row) => row.id}
                mobilePrimaryField="id"
                mobileStatusField="resolution_state"
                mobileDetailFields={['assessed_value_minor']}
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
                    <AdminButton variant="neutral" onClick={() => router.visit(`/admin/warranty/trade-ins/${row.id}`)}>
                        View
                    </AdminButton>
                )}
                emptyState={
                    <AdminEmptyState
                        icon="phone"
                        title="No trade-ins found"
                        description={hasActiveFilters ? 'No trade-ins match these filters.' : 'Trade-ins staff record will show up here.'}
                    />
                }
                footer={
                    tradeIns.total > 0 ? (
                        <AdminPagination
                            currentPage={tradeIns.current_page}
                            totalItems={tradeIns.total}
                            pageSize={tradeIns.per_page}
                            onPageChange={goToPage}
                        />
                    ) : undefined
                }
            />
        </AdminShell>
    );
}
