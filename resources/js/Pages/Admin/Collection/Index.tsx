import { Head, router } from '@inertiajs/react';
import AdminShell from '../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../Components/Admin/AdminPageHead';
import AdminButton from '../../../Components/Admin/AdminButton';
import AdminBadge, { AdminBadgeStatus } from '../../../Components/Admin/AdminBadge';
import AdminEmptyState from '../../../Components/Admin/AdminEmptyState';
import AdminPagination from '../../../Components/Admin/AdminPagination';
import ResponsiveDataTable, { DataTableColumn } from '../../../Components/Tables/ResponsiveDataTable';
import { formatNaira } from '../../../lib/money';

type CollectionRow = {
    id: number;
    source_type: string;
    source_id: number;
    shop_id: number;
    context: string;
    status: string;
    collection_deadline_at: string;
    accrued_storage_fee_minor: number;
};

type Paginated<T> = { data: T[]; current_page: number; last_page: number; per_page: number; total: number };

interface CollectionIndexProps {
    cases: Paginated<CollectionRow>;
    filters: { status: string; shop_id: number | null };
}

const STATUS_BADGE: Record<string, AdminBadgeStatus> = {
    pending: 'neutral',
    overdue: 'warning',
    abandoned: 'danger',
    resolved: 'success',
};

const COLUMNS: DataTableColumn<CollectionRow>[] = [
    { key: 'source_id', label: 'Repair', render: (row) => `${row.source_type.replace('_', ' ')} #${row.source_id}` },
    { key: 'context', label: 'Context', render: (row) => row.context.replace(/_/g, ' ') },
    {
        key: 'status',
        label: 'Status',
        render: (row) => <AdminBadge status={STATUS_BADGE[row.status] ?? 'neutral'}>{row.status}</AdminBadge>,
    },
    { key: 'collection_deadline_at', label: 'Deadline', render: (row) => new Date(row.collection_deadline_at).toLocaleDateString() },
    { key: 'accrued_storage_fee_minor', label: 'Storage fee', render: (row) => formatNaira(row.accrued_storage_fee_minor) },
];

export default function CollectionIndex({ cases, filters }: CollectionIndexProps) {
    function applyStatus(status: string) {
        router.get('/admin/collection', { status: status || undefined }, { preserveState: true, preserveScroll: true, replace: true });
    }

    function goToPage(page: number) {
        router.get('/admin/collection', { status: filters.status || undefined, page }, { preserveState: true, preserveScroll: true, replace: true });
    }

    return (
        <AdminShell>
            <Head title="Collection" />

            <AdminPageHead title="Collection" description="Devices ready for collection or return, and the overdue/abandoned queue." />

            <ResponsiveDataTable
                columns={COLUMNS}
                rows={cases.data}
                getRowKey={(row) => row.id}
                mobilePrimaryField="source_id"
                mobileStatusField="status"
                mobileDetailFields={['context', 'collection_deadline_at', 'accrued_storage_fee_minor']}
                toolbar={
                    <select
                        value={filters.status}
                        onChange={(e) => applyStatus(e.target.value)}
                        className="border border-admin-border rounded-admin-button px-2 py-1.5 text-xs bg-admin-bg outline-none focus:border-admin-blue focus:ring-2 focus:ring-admin-focus"
                    >
                        <option value="">All statuses</option>
                        <option value="pending">Pending</option>
                        <option value="overdue">Overdue</option>
                        <option value="abandoned">Abandoned</option>
                        <option value="resolved">Resolved</option>
                    </select>
                }
                renderActions={(row) => (
                    <AdminButton variant="neutral" onClick={() => router.visit(`/admin/collection/${row.id}`)}>
                        View
                    </AdminButton>
                )}
                emptyState={<AdminEmptyState icon="box-seam" title="No collection cases" description="Devices ready for collection or return will show up here." />}
                footer={
                    cases.total > 0 ? (
                        <AdminPagination currentPage={cases.current_page} totalItems={cases.total} pageSize={cases.per_page} onPageChange={goToPage} />
                    ) : undefined
                }
            />
        </AdminShell>
    );
}
