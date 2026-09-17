import { useEffect, useRef, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminShell from '../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../Components/Admin/AdminPageHead';
import AdminBadge, { AdminBadgeStatus } from '../../../../Components/Admin/AdminBadge';
import AdminButton from '../../../../Components/Admin/AdminButton';
import AdminEmptyState from '../../../../Components/Admin/AdminEmptyState';
import AdminPagination from '../../../../Components/Admin/AdminPagination';
import ResponsiveDataTable, { DataTableColumn } from '../../../../Components/Tables/ResponsiveDataTable';

interface WarrantyClaimRow {
    id: number;
    customer: { name: string; phone: string } | null;
    resolution_state: string;
    selected_remedy: string | null;
    created_at: string;
}

interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface WarrantyClaimsIndexProps {
    claims: Paginated<WarrantyClaimRow>;
    filters: { search: string; status: string };
}

const STATE_BADGE: Record<string, AdminBadgeStatus> = {
    submitted: 'info',
    under_assessment: 'warning',
    eligible: 'success',
    not_eligible: 'danger',
    remedy_selected: 'warning',
    resolved: 'neutral',
};

const STATE_LABEL: Record<string, string> = {
    submitted: 'Submitted',
    under_assessment: 'Under assessment',
    eligible: 'Eligible',
    not_eligible: 'Not eligible',
    remedy_selected: 'Remedy selected',
    resolved: 'Resolved',
};

const COLUMNS: DataTableColumn<WarrantyClaimRow>[] = [
    { key: 'id', label: 'Claim #' },
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
    { key: 'selected_remedy', label: 'Remedy', render: (row) => row.selected_remedy ?? '—' },
];

export default function WarrantyClaimsIndex({ claims, filters }: WarrantyClaimsIndexProps) {
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
                '/admin/warranty/claims',
                { search: search || undefined, status: status || undefined },
                { preserveState: true, preserveScroll: true, replace: true },
            );
        }, 350);

        return () => clearTimeout(timeout);
    }, [search, status]);

    function goToPage(page: number) {
        router.get(
            '/admin/warranty/claims',
            { search: search || undefined, status: status || undefined, page },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function clearFilters() {
        skipNextFetch.current = true;
        setSearch('');
        setStatus('');
        router.get('/admin/warranty/claims', {}, { preserveState: true, preserveScroll: true, replace: true });
    }

    return (
        <AdminShell>
            <Head title="Warranty claims" />

            <AdminPageHead
                title="Warranty claims"
                description="Assess eligibility, select remedies, and resolve customer warranty claims."
                actions={<AdminButton onClick={() => router.visit('/admin/warranty/claims/create')}>New claim</AdminButton>}
            />

            <ResponsiveDataTable
                columns={COLUMNS}
                rows={claims.data}
                getRowKey={(row) => row.id}
                mobilePrimaryField="id"
                mobileStatusField="resolution_state"
                mobileDetailFields={['selected_remedy']}
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
                    <AdminButton variant="neutral" onClick={() => router.visit(`/admin/warranty/claims/${row.id}`)}>
                        View
                    </AdminButton>
                )}
                emptyState={
                    <AdminEmptyState
                        icon="shield-exclamation"
                        title="No warranty claims found"
                        description={hasActiveFilters ? 'No claims match these filters.' : 'Claims customers submit will show up here.'}
                    />
                }
                footer={
                    claims.total > 0 ? (
                        <AdminPagination
                            currentPage={claims.current_page}
                            totalItems={claims.total}
                            pageSize={claims.per_page}
                            onPageChange={goToPage}
                        />
                    ) : undefined
                }
            />
        </AdminShell>
    );
}
