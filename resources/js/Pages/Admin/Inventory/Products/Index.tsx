import { useEffect, useRef, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminShell from '../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../Components/Admin/AdminPageHead';
import AdminButton from '../../../../Components/Admin/AdminButton';
import AdminBadge from '../../../../Components/Admin/AdminBadge';
import AdminEmptyState from '../../../../Components/Admin/AdminEmptyState';
import AdminPagination from '../../../../Components/Admin/AdminPagination';
import InventorySubNav from '../../../../Components/Admin/InventorySubNav';
import Icon from '../../../../Components/Icons/Icon';
import ResponsiveDataTable, { DataTableColumn } from '../../../../Components/Tables/ResponsiveDataTable';

type ProductRow = {
    id: number;
    sku_code: string;
    brand: string;
    model: string;
    category: string;
    is_serialized: boolean;
    cost_price_minor: number;
    selling_price_minor: number;
    low_stock_threshold: number | null;
};

type Paginated<T> = { data: T[]; current_page: number; last_page: number; per_page: number; total: number };

interface ProductsIndexProps {
    products: Paginated<ProductRow>;
    filters: { search: string; category: string; brand: string };
}

function formatNaira(minor: number): string {
    return `₦${(minor / 100).toLocaleString('en-NG', { minimumFractionDigits: 0 })}`;
}

const COLUMNS: DataTableColumn<ProductRow>[] = [
    { key: 'sku_code', label: 'SKU' },
    { key: 'brand', label: 'Brand' },
    { key: 'model', label: 'Model' },
    { key: 'category', label: 'Category' },
    {
        key: 'is_serialized',
        label: 'Type',
        render: (row) => (
            <AdminBadge status={row.is_serialized ? 'info' : 'neutral'}>{row.is_serialized ? 'Serialized' : 'Bulk'}</AdminBadge>
        ),
    },
    { key: 'selling_price_minor', label: 'Price', render: (row) => formatNaira(row.selling_price_minor) },
];

export default function ProductsIndex({ products, filters }: ProductsIndexProps) {
    const [search, setSearch] = useState(filters.search);
    const [category, setCategory] = useState(filters.category);
    const [brand, setBrand] = useState(filters.brand);
    const skipNextFetch = useRef(true);
    const hasActiveFilters = Boolean(filters.search || filters.category || filters.brand);

    useEffect(() => {
        if (skipNextFetch.current) {
            skipNextFetch.current = false;
            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                '/admin/inventory/products',
                { search: search || undefined, category: category || undefined, brand: brand || undefined },
                { preserveState: true, preserveScroll: true, replace: true },
            );
        }, 350);

        return () => clearTimeout(timeout);
    }, [search, category, brand]);

    function goToPage(page: number) {
        router.get(
            '/admin/inventory/products',
            { search: search || undefined, category: category || undefined, brand: brand || undefined, page },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function clearFilters() {
        skipNextFetch.current = true;
        setSearch('');
        setCategory('');
        setBrand('');
        router.get('/admin/inventory/products', {}, { preserveState: true, preserveScroll: true, replace: true });
    }

    return (
        <AdminShell>
            <Head title="Products" />

            <AdminPageHead
                title="Inventory"
                description="Manage the product catalog, stock, transfers, and bulk imports."
                actions={<AdminButton onClick={() => router.visit('/admin/inventory/products/create')}>New product</AdminButton>}
            />

            <InventorySubNav />

            <ResponsiveDataTable
                columns={COLUMNS}
                rows={products.data}
                getRowKey={(row) => row.id}
                mobilePrimaryField="model"
                mobileStatusField="is_serialized"
                mobileDetailFields={['sku_code', 'brand', 'category', 'selling_price_minor']}
                toolbar={
                    <>
                        <div className="relative flex-1 min-w-[160px] max-w-[260px]">
                            <Icon name="search" className="absolute left-2.5 top-1/2 -translate-y-1/2 text-admin-text3 text-xs" />
                            <input
                                type="text"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Filter by SKU, brand, model…"
                                className="w-full border border-admin-border rounded-admin-button pl-8 pr-3 py-1.5 text-xs bg-admin-bg outline-none focus:border-admin-blue focus:ring-2 focus:ring-admin-focus"
                            />
                        </div>
                        <input
                            type="text"
                            value={category}
                            onChange={(e) => setCategory(e.target.value)}
                            placeholder="Category"
                            className="border border-admin-border rounded-admin-button px-2.5 py-1.5 text-xs bg-admin-bg w-28"
                        />
                        <input
                            type="text"
                            value={brand}
                            onChange={(e) => setBrand(e.target.value)}
                            placeholder="Brand"
                            className="border border-admin-border rounded-admin-button px-2.5 py-1.5 text-xs bg-admin-bg w-28"
                        />
                        <div className="flex-1" />
                        {hasActiveFilters && (
                            <AdminButton type="button" variant="warning" onClick={clearFilters}>
                                Clear filters
                            </AdminButton>
                        )}
                    </>
                }
                renderActions={(row) => (
                    <AdminButton variant="neutral" onClick={() => router.visit(`/admin/inventory/products/${row.id}`)}>
                        View
                    </AdminButton>
                )}
                emptyState={
                    <AdminEmptyState
                        icon="box-seam"
                        title="No products found"
                        description={hasActiveFilters ? 'No products match these filters.' : 'Products you add will show up here.'}
                    />
                }
                footer={
                    products.total > 0 ? (
                        <AdminPagination
                            currentPage={products.current_page}
                            totalItems={products.total}
                            pageSize={products.per_page}
                            onPageChange={goToPage}
                        />
                    ) : undefined
                }
            />
        </AdminShell>
    );
}
