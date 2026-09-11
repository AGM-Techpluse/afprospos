import { useEffect, useRef, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminShell from '../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../Components/Admin/AdminPageHead';
import AdminBadge from '../../../Components/Admin/AdminBadge';
import AdminButton from '../../../Components/Admin/AdminButton';
import AdminEmptyState from '../../../Components/Admin/AdminEmptyState';
import AdminPagination from '../../../Components/Admin/AdminPagination';
import ResponsiveDataTable, { DataTableColumn } from '../../../Components/Tables/ResponsiveDataTable';

type ShopRow = {
    id: number;
    name: string;
    sku_prefix_code: string;
    address: string;
    contact_phone: string;
    contact_email: string;
    status: 'active' | 'inactive';
};

type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
};

interface ShopsIndexProps {
    shops: Paginated<ShopRow>;
    filters: { search: string; status: string };
}

const COLUMNS: DataTableColumn<ShopRow>[] = [
    { key: 'name', label: 'Name' },
    { key: 'sku_prefix_code', label: 'SKU prefix' },
    { key: 'address', label: 'Address' },
    {
        key: 'status',
        label: 'Status',
        render: (row) => (
            <AdminBadge status={row.status === 'active' ? 'success' : 'neutral'}>
                {row.status === 'active' ? 'Active' : 'Inactive'}
            </AdminBadge>
        ),
    },
];

const NO_FILTERS = { search: '', status: '' };

export default function ShopsIndex({ shops, filters }: ShopsIndexProps) {
    const [search, setSearch] = useState(filters.search);
    const [status, setStatus] = useState(filters.status);
    const [refreshing, setRefreshing] = useState(false);
    const skipNextFetch = useRef(true);

    const hasActiveFilters = Boolean(filters.search || filters.status);

    useEffect(() => {
        if (skipNextFetch.current) {
            skipNextFetch.current = false;
            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                '/admin/shops',
                { search: search || undefined, status: status || undefined },
                { preserveState: true, preserveScroll: true, replace: true },
            );
        }, 350);

        return () => clearTimeout(timeout);
    }, [search, status]);

    function goToPage(page: number) {
        router.get(
            '/admin/shops',
            { search: search || undefined, status: status || undefined, page },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function refresh() {
        router.reload({
            only: ['shops'],
            onStart: () => setRefreshing(true),
            onFinish: () => setRefreshing(false),
        });
    }

    function clearFilters() {
        skipNextFetch.current = true;
        setSearch(NO_FILTERS.search);
        setStatus(NO_FILTERS.status);
        router.get('/admin/shops', {}, { preserveState: true, preserveScroll: true, replace: true });
    }

    return (
        <AdminShell>
            <Head title="Shops" />

            <AdminPageHead
                title="Shops"
                description="Manage shop locations, contact details, and SKU prefixes."
                actions={
                    <AdminButton onClick={() => router.visit('/admin/shops/create')}>New shop</AdminButton>
                }
            />

            <ResponsiveDataTable
                columns={COLUMNS}
                rows={shops.data}
                getRowKey={(row) => row.id}
                mobilePrimaryField="name"
                mobileStatusField="status"
                mobileDetailFields={['sku_prefix_code', 'address']}
                loading={refreshing}
                toolbar={
                    <>
                        <input
                            type="text"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Filter by name or SKU prefix…"
                            className="flex-1 min-w-[160px] max-w-[260px] border border-admin-border rounded-admin-button px-2.5 py-1.5 text-xs bg-admin-bg outline-none focus:border-admin-blue focus:ring-2 focus:ring-admin-focus"
                        />
                        <select
                            value={status}
                            onChange={(e) => setStatus(e.target.value)}
                            className="border border-admin-border rounded-admin-button text-xs py-1.5 px-2 bg-admin-bg"
                        >
                            <option value="">All statuses</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
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
                    <AdminButton variant="neutral" onClick={() => router.visit(`/admin/shops/${row.id}`)}>
                        View
                    </AdminButton>
                )}
                emptyState={
                    <AdminEmptyState
                        icon="shop"
                        title="No shops found"
                        description={hasActiveFilters ? 'No shops match these filters.' : 'Shops you create will show up here.'}
                    />
                }
                footer={
                    shops.total > 0 ? (
                        <AdminPagination
                            currentPage={shops.current_page}
                            totalItems={shops.total}
                            pageSize={shops.per_page}
                            onPageChange={goToPage}
                        />
                    ) : undefined
                }
            />
        </AdminShell>
    );
}
