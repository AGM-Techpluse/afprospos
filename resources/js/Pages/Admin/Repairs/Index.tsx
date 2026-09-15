import { Head, router } from '@inertiajs/react';
import AdminShell from '../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../Components/Admin/AdminPageHead';
import AdminButton from '../../../Components/Admin/AdminButton';
import AdminBadge, { AdminBadgeStatus } from '../../../Components/Admin/AdminBadge';
import AdminEmptyState from '../../../Components/Admin/AdminEmptyState';
import AdminPagination from '../../../Components/Admin/AdminPagination';
import Icon from '../../../Components/Icons/Icon';
import ResponsiveDataTable, { DataTableColumn } from '../../../Components/Tables/ResponsiveDataTable';

type RepairRow = {
    id: number;
    shop_id: number;
    customer_id: number;
    device_make: string;
    device_model: string;
    technician_staff_id: number | null;
    repair_status: string;
    financial_status: string;
    created_at: string;
};

type Paginated<T> = { data: T[]; current_page: number; last_page: number; per_page: number; total: number };

interface RepairsIndexProps {
    repairs: Paginated<RepairRow>;
    filters: { status: string; shop_id: number | null };
}

const STATUS_BADGE: Record<string, AdminBadgeStatus> = {
    received: 'neutral',
    diagnosing: 'info',
    awaiting_authorization: 'warning',
    payment_overdue: 'warning',
    expired_cancelled: 'danger',
    awaiting_parts: 'info',
    in_progress: 'info',
    completed: 'success',
    unrepairable: 'danger',
    failed_requires_resolution: 'danger',
};

const COLUMNS: DataTableColumn<RepairRow>[] = [
    { key: 'device_make', label: 'Device', render: (row) => `${row.device_make} ${row.device_model}` },
    { key: 'customer_id', label: 'Customer', render: (row) => `#${row.customer_id}` },
    {
        key: 'repair_status',
        label: 'Status',
        render: (row) => <AdminBadge status={STATUS_BADGE[row.repair_status] ?? 'neutral'}>{row.repair_status.replace(/_/g, ' ')}</AdminBadge>,
    },
    { key: 'financial_status', label: 'Financial', render: (row) => row.financial_status.replace(/_/g, ' ') },
    { key: 'created_at', label: 'Received', render: (row) => new Date(row.created_at).toLocaleDateString() },
];

export default function RepairsIndex({ repairs, filters }: RepairsIndexProps) {
    function applyStatus(status: string) {
        router.get('/admin/repairs', { status: status || undefined }, { preserveState: true, preserveScroll: true, replace: true });
    }

    function goToPage(page: number) {
        router.get('/admin/repairs', { status: filters.status || undefined, page }, { preserveState: true, preserveScroll: true, replace: true });
    }

    return (
        <AdminShell>
            <Head title="Repairs" />

            <AdminPageHead
                title="Repairs"
                description="Intake, diagnosis, and repair progress."
                actions={
                    <AdminButton onClick={() => router.visit('/admin/repairs/create')}>
                        <Icon name="plus-lg" className="mr-1.5" />
                        New repair
                    </AdminButton>
                }
            />

            <ResponsiveDataTable
                columns={COLUMNS}
                rows={repairs.data}
                getRowKey={(row) => row.id}
                mobilePrimaryField="device_make"
                mobileStatusField="repair_status"
                mobileDetailFields={['customer_id', 'financial_status', 'created_at']}
                toolbar={
                    <select
                        value={filters.status}
                        onChange={(e) => applyStatus(e.target.value)}
                        className="border border-admin-border rounded-admin-button px-2 py-1.5 text-xs bg-admin-bg outline-none focus:border-admin-blue focus:ring-2 focus:ring-admin-focus"
                    >
                        <option value="">All statuses</option>
                        {Object.keys(STATUS_BADGE).map((status) => (
                            <option key={status} value={status}>
                                {status.replace(/_/g, ' ')}
                            </option>
                        ))}
                    </select>
                }
                renderActions={(row) => (
                    <AdminButton variant="neutral" onClick={() => router.visit(`/admin/repairs/${row.id}`)}>
                        View
                    </AdminButton>
                )}
                emptyState={<AdminEmptyState icon="tools" title="No repairs yet" description="New repair jobs will show up here." />}
                footer={
                    repairs.total > 0 ? (
                        <AdminPagination currentPage={repairs.current_page} totalItems={repairs.total} pageSize={repairs.per_page} onPageChange={goToPage} />
                    ) : undefined
                }
            />
        </AdminShell>
    );
}
