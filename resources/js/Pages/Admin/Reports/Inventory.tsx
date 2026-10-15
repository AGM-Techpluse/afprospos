import ReportMetricCard from '../../../Features/Reports/ReportMetricCard';
import { DoughnutBreakdown, HorizontalBar } from '../../../Features/Reports/ReportCharts';
import { ReportPanel, ReportShell } from '../../../Features/Reports/ReportShell';
import { ReportFilters, ShopOption } from '../../../Features/Reports/types';
import ResponsiveDataTable, { DataTableColumn } from '../../../Components/Tables/ResponsiveDataTable';
import { formatNaira } from '../../../lib/money';

type LowStockRow = {
    product: string;
    sku_code: string;
    shop_id: number;
    on_hand: number;
    reserved: number;
    available: number;
    low_stock_threshold: number;
};

type Report = {
    summary: Record<string, number>;
    stock_by_category: { label: string; value: number; value_minor: number }[];
    low_stock: LowStockRow[];
    transfers: { label: string; value: number }[];
    serialized_status: { label: string; value: number }[];
};

const lowStockColumns: DataTableColumn<LowStockRow>[] = [
    { key: 'product', label: 'Product' },
    { key: 'sku_code', label: 'SKU' },
    { key: 'available', label: 'Available' },
    { key: 'low_stock_threshold', label: 'Threshold' },
    { key: 'reserved', label: 'Reserved' },
];

export default function InventoryReports({ report, filters, shops }: { report: Report; filters: ReportFilters; shops: ShopOption[] }) {
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

            <ResponsiveDataTable
                columns={lowStockColumns}
                rows={report.low_stock}
                getRowKey={(row) => `${row.shop_id}-${row.sku_code}`}
                mobilePrimaryField="product"
                mobileStatusField="available"
                mobileDetailFields={['sku_code', 'low_stock_threshold', 'reserved']}
            />
        </ReportShell>
    );
}

