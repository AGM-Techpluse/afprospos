import ReportMetricCard from '../../../Features/Reports/ReportMetricCard';
import { DoughnutBreakdown, HorizontalBar, TrendLine } from '../../../Features/Reports/ReportCharts';
import { ReportPanel, ReportShell } from '../../../Features/Reports/ReportShell';
import { ReportFilters, ShopOption } from '../../../Features/Reports/types';
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

const productColumns: DataTableColumn<ProductRow>[] = [
    { key: 'product', label: 'Product' },
    { key: 'quantity', label: 'Qty sold' },
    { key: 'revenue_minor', label: 'Revenue', render: (row) => formatNaira(row.revenue_minor) },
];

export default function SalesReports({ report, filters, shops }: { report: Report; filters: ReportFilters; shops: ShopOption[] }) {
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

            <ResponsiveDataTable
                columns={productColumns}
                rows={report.top_products}
                getRowKey={(row) => row.product}
                mobilePrimaryField="product"
                mobileDetailFields={['quantity', 'revenue_minor']}
            />
        </ReportShell>
    );
}

