import { useEffect, useRef, useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import AdminShell from '../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../Components/Admin/AdminPageHead';
import AdminButton from '../../../../Components/Admin/AdminButton';
import AdminBadge from '../../../../Components/Admin/AdminBadge';
import AdminEmptyState from '../../../../Components/Admin/AdminEmptyState';
import AdminPagination from '../../../../Components/Admin/AdminPagination';
import InventorySubNav from '../../../../Components/Admin/InventorySubNav';
import AdminSelect from '../../../../Components/Admin/Forms/AdminSelect';
import Icon from '../../../../Components/Icons/Icon';
import ResponsiveDataTable, { DataTableColumn } from '../../../../Components/Tables/ResponsiveDataTable';
import ProductImage from '../../../../Components/ProductImage';
import OverflowMarquee from '../../../../Components/OverflowMarquee';

type ProductRow = {
    id: number;
    sku_code: string;
    brand: string;
    model: string;
    category: string;
    image_url: string | null;
    attributes: Record<string, unknown>;
    variant_label: string;
    variant_position: number;
    variant_count: number;
    stock_available: number;
    stock_reserved: number;
    is_serialized: boolean;
    cost_price_minor: number;
    selling_price_minor: number;
    low_stock_threshold: number | null;
};

type Shop = { id: number; name: string };

type Paginated<T> = { data: T[]; current_page: number; last_page: number; per_page: number; total: number };

interface ProductsIndexProps {
    products: Paginated<ProductRow>;
    filters: { search: string; category: string; brand: string; shop_id: number | null; variant: string };
    categories: string[];
}

function formatNaira(minor: number): string {
    return `₦${(minor / 100).toLocaleString('en-NG', { minimumFractionDigits: 0 })}`;
}

const COLUMNS: DataTableColumn<ProductRow>[] = [
    {
        key: 'image',
        label: '',
        render: (row) => <ProductImage src={row.image_url} alt={`${row.brand} ${row.model}`} aspect="square" category={row.category} className="w-10" />,
    },
    {
        key: 'product',
        label: 'Product',
        render: (row) => (
            <span className="block w-40 min-w-0">
                <OverflowMarquee text={row.model} className="font-semibold text-admin-text" />
                <OverflowMarquee text={row.brand} className="text-admin-xs text-admin-text3" />
            </span>
        ),
    },
    {
        key: 'variant',
        label: 'Variant',
        render: (row) => (
            <span className="w-44 min-w-0 flex flex-col items-start gap-1">
                <AdminBadge status={row.variant_count > 1 ? 'info' : 'neutral'}>
                    {row.variant_count > 1 ? `Variant ${row.variant_position} of ${row.variant_count}` : 'Single SKU'}
                </AdminBadge>
                <span className="block w-full min-w-0">
                    <OverflowMarquee
                        text={row.variant_label || (row.variant_count > 1 ? 'No attributes set' : 'Base configuration')}
                        className="text-admin-xs text-admin-text2"
                    />
                </span>
                <span className="text-admin-xs font-mono text-admin-text3">{row.sku_code}</span>
            </span>
        ),
    },
    { key: 'category', label: 'Category' },
    {
        key: 'is_serialized',
        label: 'Type',
        render: (row) => (
            <AdminBadge status={row.is_serialized ? 'info' : 'neutral'}>{row.is_serialized ? 'Serialized' : 'Bulk'}</AdminBadge>
        ),
    },
    {
        key: 'stock',
        label: 'Stock',
        render: (row) => (
            <span className="block whitespace-nowrap">
                <span className="font-semibold text-admin-text">{row.stock_available} available</span>
                {row.stock_reserved > 0 && (
                    <span className="block text-admin-xs text-admin-yellow-text">{row.stock_reserved} reserved</span>
                )}
            </span>
        ),
    },
    { key: 'selling_price_minor', label: 'Price', render: (row) => formatNaira(row.selling_price_minor) },
];

export default function ProductsIndex({ products, filters, categories }: ProductsIndexProps) {
    // Already shared on every Admin page by HandleInertiaRequests — avoids a second, redundant query for the same accessible-shops list.
    const shops: Shop[] = (usePage().props as any).shopSwitcher?.accessible ?? [];
    const [search, setSearch] = useState(filters.search);
    const [category, setCategory] = useState(filters.category);
    const [brand, setBrand] = useState(filters.brand);
    const [shopId, setShopId] = useState(filters.shop_id);
    const [variant, setVariant] = useState(filters.variant);
    const [loading, setLoading] = useState(false);
    const skipNextFetch = useRef(true);
    const hasActiveFilters = Boolean(search || category || brand || shopId || variant);

    useEffect(() => {
        if (skipNextFetch.current) {
            skipNextFetch.current = false;
            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                '/admin/inventory/products',
                { search: search || undefined, category: category || undefined, brand: brand || undefined, shop_id: shopId ?? '', variant: variant || undefined },
                { only: ['products'], preserveState: true, preserveScroll: true, replace: true, showProgress: false, onStart: () => setLoading(true), onFinish: () => setLoading(false) },
            );
        }, 350);

        return () => clearTimeout(timeout);
    }, [search, category, brand, shopId, variant]);

    function goToPage(page: number) {
        router.get(
            '/admin/inventory/products',
            { search: search || undefined, category: category || undefined, brand: brand || undefined, shop_id: shopId ?? '', variant: variant || undefined, page },
            { only: ['products'], preserveState: true, preserveScroll: true, replace: true, showProgress: false, onStart: () => setLoading(true), onFinish: () => setLoading(false) },
        );
    }

    function clearFilters() {
        skipNextFetch.current = true;
        setSearch('');
        setCategory('');
        setBrand('');
        setShopId(null);
        setVariant('');
        router.get(
            '/admin/inventory/products',
            {},
            { only: ['products'], preserveState: true, preserveScroll: true, replace: true, showProgress: false, onStart: () => setLoading(true), onFinish: () => setLoading(false) },
        );
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
                mobilePrimaryField="product"
                mobileStatusField="is_serialized"
                mobileDetailFields={['variant', 'category', 'stock', 'selling_price_minor']}
                loading={loading}
                toolbar={
                    <>
                        <div className="relative flex-1 min-w-[160px] max-w-[260px]">
                            <Icon name="search" className="absolute left-2.5 top-1/2 -translate-y-1/2 text-admin-text3 text-xs" />
                            <input
                                type="text"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Filter by product, SKU or attributes…"
                                className="w-full border border-admin-border rounded-admin-button pl-8 pr-3 py-1.5 text-xs bg-admin-bg outline-none focus:border-admin-blue focus:ring-2 focus:ring-admin-focus"
                            />
                        </div>
                        <AdminSelect aria-label="Filter by category" value={category} onChange={(e) => setCategory(e.target.value)} containerClassName="mb-0">
                            <option value="">All categories</option>
                            {categories.map((value) => (
                                <option key={value} value={value}>{value}</option>
                            ))}
                        </AdminSelect>
                        <input
                            type="text"
                            value={brand}
                            onChange={(e) => setBrand(e.target.value)}
                            placeholder="Brand"
                            className="border border-admin-border rounded-admin-button px-2.5 py-1.5 text-xs bg-admin-bg w-28"
                        />
                        <AdminSelect aria-label="Filter by shop" value={shopId ?? ''} onChange={(e) => setShopId(e.target.value ? Number(e.target.value) : null)} containerClassName="mb-0">
                            <option value="">All shops</option>
                            {shops.map((shop) => (
                                <option key={shop.id} value={shop.id}>
                                    {shop.name}
                                </option>
                            ))}
                        </AdminSelect>
                        <AdminSelect aria-label="Filter by variant type" value={variant} onChange={(e) => setVariant(e.target.value)} containerClassName="mb-0">
                            <option value="">All product rows</option>
                            <option value="variants">Variants only</option>
                            <option value="single">Single-SKU products</option>
                        </AdminSelect>
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
