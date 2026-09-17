import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminShell from '../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../Components/Admin/AdminPageHead';
import AdminButton from '../../../Components/Admin/AdminButton';
import AdminBulkActionBar from '../../../Components/Admin/AdminBulkActionBar';
import AdminBadge, { AdminBadgeStatus } from '../../../Components/Admin/AdminBadge';
import AdminEmptyState from '../../../Components/Admin/AdminEmptyState';
import AdminPagination from '../../../Components/Admin/AdminPagination';
import Icon from '../../../Components/Icons/Icon';
import ResponsiveDataTable, { DataTableColumn } from '../../../Components/Tables/ResponsiveDataTable';

type RepairRow = {
    id: number;
    shop_id: number;
    customer_id: number;
    customer_name: string | null;
    device_make: string;
    device_model: string;
    technician_staff_id: number | null;
    repair_status: string;
    financial_status: string;
    created_at: string;
};

type Shop = { id: number; name: string };

type Paginated<T> = { data: T[]; current_page: number; last_page: number; per_page: number; total: number };

interface RepairsIndexProps {
    repairs: Paginated<RepairRow>;
    filters: { status: string; shop_id: number | null };
    shops: Shop[];
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

export default function RepairsIndex({ repairs, filters, shops }: RepairsIndexProps) {
    const [selectedIds, setSelectedIds] = useState<(string | number)[]>([]);
    const shopName = (shopId: number) => shops.find((shop) => shop.id === shopId)?.name ?? 'Unknown shop';

    function exportMatchingFilters() {
        const params = new URLSearchParams();
        if (filters.status) params.set('status', filters.status);
        if (filters.shop_id !== null && filters.shop_id !== undefined) params.set('shop_id', String(filters.shop_id));
        window.location.href = `/admin/repairs/export?${params.toString()}`;
    }

    function exportSelected() {
        const params = new URLSearchParams();
        selectedIds.forEach((id) => params.append('ids[]', String(id)));
        window.location.href = `/admin/repairs/export?${params.toString()}`;
    }

    const columns: DataTableColumn<RepairRow>[] = [
        { key: 'device_make', label: 'Device', render: (row) => `${row.device_make} ${row.device_model}` },
        { key: 'shop_id', label: 'Shop', render: (row) => shopName(row.shop_id) },
        { key: 'customer_id', label: 'Customer', render: (row) => row.customer_name ?? 'Customer' },
        {
            key: 'repair_status',
            label: 'Status',
            render: (row) => <AdminBadge status={STATUS_BADGE[row.repair_status] ?? 'neutral'}>{row.repair_status.replace(/_/g, ' ')}</AdminBadge>,
        },
        { key: 'financial_status', label: 'Financial', render: (row) => row.financial_status.replace(/_/g, ' ') },
        { key: 'created_at', label: 'Received', render: (row) => new Date(row.created_at).toLocaleDateString() },
    ];

    function applyFilters(next: { status?: string; shop_id?: number | null }) {
        router.get(
            '/admin/repairs',
            {
                status: next.status ?? filters.status ?? undefined,
                shop_id: 'shop_id' in next ? (next.shop_id ?? '') : (filters.shop_id ?? undefined),
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function goToPage(page: number) {
        router.get(
            '/admin/repairs',
            { status: filters.status || undefined, shop_id: filters.shop_id ?? undefined, page },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    return (
        <AdminShell>
            <Head title="Repairs" />

            <AdminPageHead
                title="Repairs"
                description="Intake, diagnosis, and repair progress."
                actions={
                    <div className="flex items-center gap-2">
                        <AdminButton variant="neutral" onClick={exportMatchingFilters}>Export all matching filters</AdminButton>
                        <AdminButton onClick={() => router.visit('/admin/repairs/create')}>
                            <Icon name="plus-lg" className="mr-1.5" />
                            New repair
                        </AdminButton>
                    </div>
                }
            />

            <AdminBulkActionBar
                selectedCount={selectedIds.length}
                onClear={() => setSelectedIds([])}
                actions={
                    <AdminButton type="button" variant="neutral" onClick={exportSelected}>
                        Export selected ({selectedIds.length})
                    </AdminButton>
                }
            />

            <ResponsiveDataTable
                columns={columns}
                rows={repairs.data}
                getRowKey={(row) => row.id}
                mobilePrimaryField="device_make"
                mobileStatusField="repair_status"
                mobileDetailFields={['shop_id', 'customer_id', 'financial_status', 'created_at']}
                selectable
                selectedIds={selectedIds}
                onSelectionChange={setSelectedIds}
                toolbar={
                    <>
                        <select
                            value={filters.shop_id ?? ''}
                            onChange={(e) => applyFilters({ shop_id: e.target.value ? Number(e.target.value) : null })}
                            className="border border-admin-border rounded-admin-button px-2 py-1.5 text-xs bg-admin-bg outline-none focus:border-admin-blue focus:ring-2 focus:ring-admin-focus"
                        >
                            <option value="">All shops</option>
                            {shops.map((shop) => (
                                <option key={shop.id} value={shop.id}>
                                    {shop.name}
                                </option>
                            ))}
                        </select>
                        <select
                            value={filters.status}
                            onChange={(e) => applyFilters({ status: e.target.value })}
                            className="border border-admin-border rounded-admin-button px-2 py-1.5 text-xs bg-admin-bg outline-none focus:border-admin-blue focus:ring-2 focus:ring-admin-focus"
                        >
                            <option value="">All statuses</option>
                            {Object.keys(STATUS_BADGE).map((status) => (
                                <option key={status} value={status}>
                                    {status.replace(/_/g, ' ')}
                                </option>
                            ))}
                        </select>
                    </>
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
