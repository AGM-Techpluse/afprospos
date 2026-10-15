import ReportMetricCard from '../../../Features/Reports/ReportMetricCard';
import { DoughnutBreakdown, HorizontalBar } from '../../../Features/Reports/ReportCharts';
import { ReportPanel, ReportShell } from '../../../Features/Reports/ReportShell';
import { ReportFilters, ShopOption } from '../../../Features/Reports/types';
import ResponsiveDataTable, { DataTableColumn } from '../../../Components/Tables/ResponsiveDataTable';
import { formatNaira } from '../../../lib/money';

type CashierRow = { staff_id: number; name: string; sales_count: number; revenue_minor: number };
type TechnicianRow = { staff_id: number; name: string; assigned: number; completed: number };
type Report = {
    summary: Record<string, number>;
    role_distribution: { label: string; value: number }[];
    cashiers: CashierRow[];
    technicians: TechnicianRow[];
    activity: { label: string; value: number }[];
};

const cashierColumns: DataTableColumn<CashierRow>[] = [
    { key: 'name', label: 'Cashier' },
    { key: 'sales_count', label: 'Sales' },
    { key: 'revenue_minor', label: 'Revenue', render: (row) => formatNaira(row.revenue_minor) },
];

export default function StaffReports({ report, filters, shops }: { report: Report; filters: ReportFilters; shops: ShopOption[] }) {
    return (
        <ReportShell title="Staff report" description="Staff coverage, roles, cashier sales, technician output, and audit activity." filters={filters} shops={shops}>
            <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4 mb-4">
                <ReportMetricCard label="Total staff" value={report.summary.total_staff} />
                <ReportMetricCard label="Active staff" value={report.summary.active_staff} />
                <ReportMetricCard label="Inactive staff" value={report.summary.inactive_staff} />
                <ReportMetricCard label="Roles" value={report.summary.role_count} />
            </div>

            <div className="grid gap-4 lg:grid-cols-2 mb-4">
                <ReportPanel title="Role distribution"><DoughnutBreakdown rows={report.role_distribution} /></ReportPanel>
                <ReportPanel title="Cashier revenue"><HorizontalBar rows={report.cashiers} labelKey="name" valueKey="revenue_minor" money /></ReportPanel>
                <ReportPanel title="Technician assignments"><HorizontalBar rows={report.technicians} labelKey="name" valueKey="assigned" /></ReportPanel>
                <ReportPanel title="Staff activity"><HorizontalBar rows={report.activity} valueKey="value" /></ReportPanel>
            </div>

            <ResponsiveDataTable
                columns={cashierColumns}
                rows={report.cashiers}
                getRowKey={(row) => row.staff_id}
                mobilePrimaryField="name"
                mobileDetailFields={['sales_count', 'revenue_minor']}
            />
        </ReportShell>
    );
}

