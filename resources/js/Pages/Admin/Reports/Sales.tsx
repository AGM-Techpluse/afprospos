import { router } from '@inertiajs/react';
import ReportMetricCard from '../../../Features/Reports/ReportMetricCard';
import { DoughnutBreakdown, HorizontalBar, TrendLine } from '../../../Features/Reports/ReportCharts';
import { ReportPanel, ReportShell } from '../../../Features/Reports/ReportShell';
import { ReportFilters, ShopOption } from '../../../Features/Reports/types';
import AdminButton from '../../../Components/Admin/AdminButton';
import AdminPagination from '../../../Components/Admin/AdminPagination';
import ResponsiveDataTable, { DataTableColumn } from '../../../Components/Tables/ResponsiveDataTable';
import { formatNaira } from '../../../lib/money';

type ProductRow = { product: string; quantity: number; revenue_minor: number };
type Report = {
    summary: Record<string, number>;
    trend: { date: string; sales_count: number; revenue_minor: number }[];
    payment_methods: { label: string; value_minor: number; count: number }[];
    top_products: ProductRow[];
    cashiers: { staff_id: number; name: string; sales_count: number; revenue_minor: number }[];
};
type SaleRow = {
    id: number;
    customer_id: number | null;
    customer_name: string | null;
    walk_in_name: string | null;
    total_minor: number;
    payment_method: string;
    invoice_number: string;
    created_at: string;
};
type Paginated<T> = { data: T[]; current_page: number; last_page: number; per_page: number; total: number };

const salesColumns: DataTableColumn<SaleRow>[] = [
    { key: 'invoice_number', label: 'Invoice' },
    { key: 'created_at', label: 'Date', render: (row) => new Date(row.created_at).toLocaleString() },
    { key: 'customer_id', label: 'Customer', render: (row) => (row.customer_id ? (row.customer_name ?? 'Customer') : row.walk_in_name ?? 'Walk-in') },
    { key: 'payment_method', label: 'Payment' },
    { key: 'total_minor', label: 'Total', render: (row) => formatNaira(row.total_minor) },
];

export default function SalesReports({ report, sales_list: salesList, filters, shops }: { report: Report; sales_list: Paginated<SaleRow>; filters: ReportFilters; shops: ShopOption[] }) {
    function goToPage(page: number) {
        router.get(
            '/admin/reports/sales',
            { range: filters.range, start_date: filters.start_date, end_date: filters.end_date, shop_id: filters.shop_id ?? '', page },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    return (
        <ReportShell title="Sales report" description="Sales revenue, cashier performance, payment methods, and product movement." filters={filters} shops={shops}>
            <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4 mb-4">
                <ReportMetricCard label="Gross sales" valueMinor={report.summary.gross_sales_minor} />
                <ReportMetricCard label="Transactions" value={report.summary.sales_count} />
                <ReportMetricCard label="Average order" valueMinor={report.summary.average_order_minor} />
                <ReportMetricCard label="Items sold" value={report.summary.items_sold} />
            </div>

            <div className="grid gap-4 lg:grid-cols-2 mb-4">
                <ReportPanel title="Revenue trend"><TrendLine rows={report.trend} series={[{ key: 'revenue_minor', label: 'Revenue', money: true }]} /></ReportPanel>
                <ReportPanel title="Payment methods"><DoughnutBreakdown rows={report.payment_methods} money /></ReportPanel>
                <ReportPanel title="Cashier revenue"><HorizontalBar rows={report.cashiers} labelKey="name" valueKey="revenue_minor" money /></ReportPanel>
                <ReportPanel title="Top products"><HorizontalBar rows={report.top_products} labelKey="product" valueKey="quantity" /></ReportPanel>
            </div>

            <h2 className="text-sm font-semibold text-admin-text mb-2">Sales in this period</h2>
            <ResponsiveDataTable
                columns={salesColumns}
                rows={salesList.data}
                getRowKey={(row) => row.id}
                mobilePrimaryField="invoice_number"
                mobileStatusField="payment_method"
                mobileDetailFields={['created_at', 'customer_id', 'total_minor']}
                renderActions={(row) => (
                    <AdminButton variant="neutral" onClick={() => router.visit(`/admin/sales/${row.id}`)}>
                        View
                    </AdminButton>
                )}
                footer={
                    salesList.total > 0 ? (
                        <AdminPagination currentPage={salesList.current_page} totalItems={salesList.total} pageSize={salesList.per_page} onPageChange={goToPage} />
                    ) : undefined
                }
            />
        </ReportShell>
    );
}
