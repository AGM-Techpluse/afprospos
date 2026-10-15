import { useState } from 'react';
import { router } from '@inertiajs/react';
import ReportMetricCard from '../../../Features/Reports/ReportMetricCard';
import { DoughnutBreakdown, HorizontalBar } from '../../../Features/Reports/ReportCharts';
import { ReportPanel, ReportShell } from '../../../Features/Reports/ReportShell';
import { ReportFilters, ShopOption } from '../../../Features/Reports/types';
import AdminButton from '../../../Components/Admin/AdminButton';
import AdminPagination from '../../../Components/Admin/AdminPagination';
import ResponsiveDataTable, { DataTableColumn } from '../../../Components/Tables/ResponsiveDataTable';

type LowStockRow = {
    sku_id: number;
    sku_code: string;
    brand: string;
    model: string;
    shop_id: number;
    on_hand: number;
    reserved: number;
    available: number;
    low_stock_threshold: number;
    is_serialized: boolean;
};

type Report = {
    summary: Record<string, number>;
    stock_by_category: { label: string; value: number; value_minor: number }[];
    transfers: { label: string; value: number }[];
    serialized_status: { label: string; value: number }[];
};
type Paginated<T> = { data: T[]; current_page: number; last_page: number; per_page: number; total: number };

const lowStockColumns: DataTableColumn<LowStockRow>[] = [
    { key: 'product', label: 'Product', render: (row) => `${row.brand} ${row.model}` },
    { key: 'sku_code', label: 'SKU' },
    { key: 'available', label: 'Available' },
    { key: 'low_stock_threshold', label: 'Threshold' },
    { key: 'reserved', label: 'Reserved' },
];

export default function InventoryReports({
    report,
    low_stock_list: lowStockList,
    filters,
    shops,
}: {
    report: Report;
    low_stock_list: Paginated<LowStockRow>;
    filters: ReportFilters;
    shops: ShopOption[];
}) {
    const [search, setSearch] = useState('');

    function reload(next: { search?: string; page?: number }) {
        router.get(
            '/admin/reports/inventory',
            {
                range: filters.range,
                start_date: filters.start_date,
                end_date: filters.end_date,
                shop_id: filters.shop_id ?? '',
                search: next.search ?? search,
                page: next.page ?? 1,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    return (
        <ReportShell title="Inventory reports" description="Stock availability, valuation, low-stock pressure, serialized units, and transfers." filters={filters} shops={shops}>
            <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4 mb-4">
                <ReportMetricCard label="On hand" value={report.summary.on_hand_units} />
                <ReportMetricCard label="Available" value={report.summary.available_units} />
                <ReportMetricCard label="Reserved" value={report.summary.reserved_units} />
                <ReportMetricCard label="Selling value" valueMinor={report.summary.selling_value_minor} />
                <ReportMetricCard label="Cost value" valueMinor={report.summary.cost_value_minor} />
                <ReportMetricCard label="Serialized units" value={report.summary.serialized_units} />
                <ReportMetricCard label="Low stock" value={report.summary.low_stock_count} />
                <ReportMetricCard label="SKUs" value={report.summary.sku_count} />
            </div>

            <div className="grid gap-4 lg:grid-cols-2 mb-4">
                <ReportPanel title="Stock by category"><HorizontalBar rows={report.stock_by_category} valueKey="value" /></ReportPanel>
                <ReportPanel title="Stock value by category"><HorizontalBar rows={report.stock_by_category} valueKey="value_minor" money /></ReportPanel>
                <ReportPanel title="Transfer status"><DoughnutBreakdown rows={report.transfers} /></ReportPanel>
                <ReportPanel title="Serialized unit status"><DoughnutBreakdown rows={report.serialized_status} /></ReportPanel>
            </div>

            <h2 className="text-sm font-semibold text-admin-text mb-2">Low stock</h2>
            <ResponsiveDataTable
                columns={lowStockColumns}
                rows={lowStockList.data}
                getRowKey={(row) => `${row.shop_id}-${row.sku_code}`}
                mobilePrimaryField="product"
                mobileStatusField="available"
                mobileDetailFields={['sku_code', 'low_stock_threshold', 'reserved']}
                toolbar={
                    <input
                        type="text"
                        value={search}
                        onChange={(e) => {
                            setSearch(e.target.value);
                            reload({ search: e.target.value, page: 1 });
                        }}
                        placeholder="Search product or SKU…"
                        className="border border-admin-border rounded-admin-button px-2.5 py-1.5 text-xs bg-admin-bg min-w-[160px]"
                    />
                }
                renderActions={(row) => (
                    <AdminButton variant="neutral" onClick={() => router.visit(`/admin/inventory/products/${row.sku_id}`)}>
                        View
                    </AdminButton>
                )}
                footer={
                    lowStockList.total > 0 ? (
                        <AdminPagination currentPage={lowStockList.current_page} totalItems={lowStockList.total} pageSize={lowStockList.per_page} onPageChange={(page) => reload({ page })} />
                    ) : undefined
                }
            />
        </ReportShell>
    );
}
