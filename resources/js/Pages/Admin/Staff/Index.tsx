import { useEffect, useRef, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AdminShell from '../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../Components/Admin/AdminPageHead';
import AdminBadge from '../../../Components/Admin/AdminBadge';
import AdminButton from '../../../Components/Admin/AdminButton';
import AdminEmptyState from '../../../Components/Admin/AdminEmptyState';
import AdminPagination from '../../../Components/Admin/AdminPagination';
import ResponsiveDataTable, { DataTableColumn } from '../../../Components/Tables/ResponsiveDataTable';

type StaffRow = {
    id: number;
    name: string;
    email: string;
    phone: string;
    status: 'active' | 'deactivated';
    roles: string[];
};

type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
};

interface StaffIndexProps {
    staff: Paginated<StaffRow>;
    filters: { search: string; status: string; role: string; shop_id: number | null };
    availableRoles: string[];
}

const COLUMNS: DataTableColumn<StaffRow>[] = [
    { key: 'name', label: 'Name' },
    { key: 'email', label: 'Email' },
    { key: 'phone', label: 'Phone' },
    {
        key: 'roles',
        label: 'Roles',
        render: (row) => (row.roles.length > 0 ? row.roles.join(', ') : '—'),
    },
    {
        key: 'status',
        label: 'Status',
        render: (row) => (
            <AdminBadge status={row.status === 'active' ? 'success' : 'neutral'}>
                {row.status === 'active' ? 'Active' : 'Deactivated'}
            </AdminBadge>
        ),
    },
];

const NO_FILTERS = { search: '', status: '', role: '' };

export default function StaffIndex({ staff, filters, availableRoles }: StaffIndexProps) {
    const { post, processing } = useForm();
    const [search, setSearch] = useState(filters.search);
    const [status, setStatus] = useState(filters.status);
    const [role, setRole] = useState(filters.role);
    const [refreshing, setRefreshing] = useState(false);
    const skipNextFetch = useRef(true);

    const hasActiveFilters = Boolean(filters.search || filters.status || filters.role || filters.shop_id);

    useEffect(() => {
        if (skipNextFetch.current) {
            skipNextFetch.current = false;
            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                '/admin/staff',
                { search: search || undefined, status: status || undefined, role: role || undefined, shop_id: filters.shop_id ?? undefined },
                { preserveState: true, preserveScroll: true, replace: true },
            );
        }, 350);

        return () => clearTimeout(timeout);
    }, [search, status, role]);

    function goToPage(page: number) {
        router.get(
            '/admin/staff',
            { search: search || undefined, status: status || undefined, role: role || undefined, shop_id: filters.shop_id ?? undefined, page },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function refresh() {
        router.reload({
            only: ['staff'],
            onStart: () => setRefreshing(true),
            onFinish: () => setRefreshing(false),
        });
    }

    function clearFilters() {
        skipNextFetch.current = true;
        setSearch(NO_FILTERS.search);
        setStatus(NO_FILTERS.status);
        setRole(NO_FILTERS.role);
        router.get('/admin/staff', {}, { preserveState: true, preserveScroll: true, replace: true });
    }

    function deactivate(id: number) {
        post(`/admin/staff/${id}/deactivate`);
    }

    return (
        <AdminShell>
            <Head title="Staff" />

            <AdminPageHead
                title="Staff"
                description={
                    filters.shop_id
                        ? `Staff with access to Shop #${filters.shop_id}.`
                        : 'Manage staff accounts, roles, and shop access.'
                }
                backHref={filters.shop_id ? `/admin/shops/${filters.shop_id}` : undefined}
                backLabel="Back to shop"
                actions={<AdminButton onClick={() => router.visit('/admin/staff/create')}>New staff</AdminButton>}
            />

            <ResponsiveDataTable
                columns={COLUMNS}
                rows={staff.data}
                getRowKey={(row) => row.id}
                mobilePrimaryField="name"
                mobileStatusField="status"
                mobileDetailFields={['email', 'phone', 'roles']}
                loading={refreshing}
                toolbar={
                    <>
                        <input
                            type="text"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Filter by name or email…"
                            className="flex-1 min-w-[160px] max-w-[260px] border border-admin-border rounded-admin-button px-2.5 py-1.5 text-xs bg-admin-bg outline-none focus:border-admin-blue focus:ring-2 focus:ring-admin-focus"
                        />
                        <select
                            value={status}
                            onChange={(e) => setStatus(e.target.value)}
                            className="border border-admin-border rounded-admin-button text-xs py-1.5 px-2 bg-admin-bg"
                        >
                            <option value="">All statuses</option>
                            <option value="active">Active</option>
                            <option value="deactivated">Deactivated</option>
                        </select>
                        <select
                            value={role}
                            onChange={(e) => setRole(e.target.value)}
                            className="border border-admin-border rounded-admin-button text-xs py-1.5 px-2 bg-admin-bg"
                        >
                            <option value="">All roles</option>
                            {availableRoles.map((r) => (
                                <option key={r} value={r}>
                                    {r}
                                </option>
                            ))}
                        </select>
                        <div className="flex-1" />
                        <AdminButton type="button" variant="neutral" onClick={refresh} isLoading={refreshing} loadingText="Refreshing…">
                            Refresh
                        </AdminButton>
                        {hasActiveFilters && (
                            <AdminButton type="button" variant="warning" onClick={clearFilters}>
                                Clear filters
                            </AdminButton>
                        )}
                    </>
                }
                renderActions={(row) => (
                    <div className="flex items-center justify-end gap-2">
                        <AdminButton variant="neutral" onClick={() => router.visit(`/admin/staff/${row.id}`)}>
                            View
                        </AdminButton>
                        {row.status === 'active' && (
                            <AdminButton
                                variant="danger"
                                isLoading={processing}
                                onClick={() => deactivate(row.id)}
                            >
                                Deactivate
                            </AdminButton>
                        )}
                    </div>
                )}
                emptyState={
                    <AdminEmptyState
                        icon="people"
                        title="No staff found"
                        description={hasActiveFilters ? 'No staff match these filters.' : 'Staff accounts you create will show up here.'}
                    />
                }
                footer={
                    staff.total > 0 ? (
                        <AdminPagination
                            currentPage={staff.current_page}
                            totalItems={staff.total}
                            pageSize={staff.per_page}
                            onPageChange={goToPage}
                        />
                    ) : undefined
                }
            />
        </AdminShell>
    );
}
