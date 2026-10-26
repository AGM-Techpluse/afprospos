import { useEffect, useRef, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminShell from '../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../Components/Admin/AdminPageHead';
import AdminBadge from '../../../Components/Admin/AdminBadge';
import AdminEmptyState from '../../../Components/Admin/AdminEmptyState';
import AdminPagination from '../../../Components/Admin/AdminPagination';
import AdminSelect from '../../../Components/Admin/Forms/AdminSelect';
import Icon from '../../../Components/Icons/Icon';
import ResponsiveDataTable, { DataTableColumn } from '../../../Components/Tables/ResponsiveDataTable';

type CustomerRow = {
    id: number;
    name: string;
    email: string | null;
    phone: string;
    is_registered: boolean;
    created_at: string;
};

type Paginated<T> = { data: T[]; current_page: number; last_page: number; per_page: number; total: number };

interface CustomersIndexProps {
    customers: Paginated<CustomerRow>;
    filters: { search: string; registration: string };
}

const COLUMNS: DataTableColumn<CustomerRow>[] = [
    { key: 'name', label: 'Name' },
    { key: 'phone', label: 'Phone' },
    { key: 'email', label: 'Email', render: (row) => row.email ?? <span className="text-admin-text3">—</span> },
    {
        key: 'is_registered',
        label: 'Status',
        render: (row) => (
            <AdminBadge status={row.is_registered ? 'success' : 'neutral'}>
                {row.is_registered ? 'Registered' : 'Not yet registered'}
            </AdminBadge>
        ),
    },
    { key: 'created_at', label: 'First seen', render: (row) => new Date(row.created_at).toLocaleDateString() },
];

export default function CustomersIndex({ customers, filters }: CustomersIndexProps) {
    const [search, setSearch] = useState(filters.search);
    const [registration, setRegistration] = useState(filters.registration);
    const [loading, setLoading] = useState(false);
    const skipNextFetch = useRef(true);
    const hasActiveFilters = Boolean(filters.search || filters.registration);

    useEffect(() => {
        if (skipNextFetch.current) {
            skipNextFetch.current = false;
            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                '/admin/customers',
                { search: search || undefined, registration: registration || undefined },
                {
                    only: ['customers', 'filters'],
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    showProgress: false,
                    onStart: () => setLoading(true),
                    onFinish: () => setLoading(false),
                },
            );
        }, 350);

        return () => clearTimeout(timeout);
    }, [search, registration]);

    function goToPage(page: number) {
        router.get(
            '/admin/customers',
            { search: search || undefined, registration: registration || undefined, page },
            { only: ['customers'], preserveState: true, preserveScroll: true, replace: true, showProgress: false },
        );
    }

    function clearFilters() {
        skipNextFetch.current = true;
        setSearch('');
        setRegistration('');
        router.get('/admin/customers', {}, { only: ['customers', 'filters'], preserveState: true, preserveScroll: true, replace: true, showProgress: false });
    }

    return (
        <AdminShell>
            <Head title="Customers" />

            <AdminPageHead
                title="Customers"
                description="Everyone recorded during a sale or repair — registered or not."
            />

            <ResponsiveDataTable
                columns={COLUMNS}
                rows={customers.data}
                getRowKey={(row) => row.id}
                mobilePrimaryField="name"
                mobileStatusField="is_registered"
                mobileDetailFields={['phone', 'email', 'created_at']}
                loading={loading}
                toolbar={
                    <>
                        <div className="relative flex-1 min-w-[160px] max-w-[260px]">
                            <Icon name="search" className="absolute left-2.5 top-1/2 -translate-y-1/2 text-admin-text3 text-xs" />
                            <input
                                type="text"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Search by name, phone, or email…"
                                className="w-full border border-admin-border rounded-admin-button pl-8 pr-3 py-1.5 text-xs bg-admin-bg outline-none focus:border-admin-blue focus:ring-2 focus:ring-admin-focus"
                            />
                        </div>
                        <AdminSelect
                            aria-label="Filter by registration status"
                            value={registration}
                            onChange={(e) => setRegistration(e.target.value)}
                            containerClassName="mb-0"
                        >
                            <option value="">All customers</option>
                            <option value="registered">Registered</option>
                            <option value="walk_in">Not yet registered</option>
                        </AdminSelect>
                        <div className="flex-1" />
                        {hasActiveFilters && (
                            <button type="button" onClick={clearFilters} className="text-xs font-semibold text-admin-blue">
                                Clear filters
                            </button>
                        )}
                    </>
                }
                emptyState={
                    <AdminEmptyState
                        icon="people"
                        title="No customers found"
                        description={hasActiveFilters ? 'No customers match these filters.' : 'Customers show up here as sales and repairs are recorded.'}
                    />
                }
                footer={
                    customers.total > 0 ? (
                        <AdminPagination
                            currentPage={customers.current_page}
                            totalItems={customers.total}
                            pageSize={customers.per_page}
                            onPageChange={goToPage}
                        />
                    ) : undefined
                }
            />
        </AdminShell>
    );
}
