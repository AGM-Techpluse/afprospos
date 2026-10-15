import ReportMetricCard from '../../../Features/Reports/ReportMetricCard';
import { DoughnutBreakdown, HorizontalBar, TrendLine } from '../../../Features/Reports/ReportCharts';
import { ReportPanel, ReportShell } from '../../../Features/Reports/ReportShell';
import { ReportFilters, ShopOption } from '../../../Features/Reports/types';
import ResponsiveDataTable, { DataTableColumn } from '../../../Components/Tables/ResponsiveDataTable';
import { formatNaira } from '../../../lib/money';

type Report = {
    summary: Record<string, number>;
    status_breakdown: { label: string; value: number }[];
    trend: { date: string; received: number; completed: number }[];
    technicians: { staff_id: number; name: string; assigned: number; completed: number; failed: number }[];
    settlements: { id: number; device_make: string; device_model: string; repair_status: string; unrepairable_settlement_state: string | null; service_charge_minor: number; refund_due_minor: number }[];
    collections: { label: string; value: number }[];
};

const settlementColumns: DataTableColumn<Report['settlements'][number]>[] = [
    { key: 'device_model', label: 'Work order', render: (row) => `${row.device_make} ${row.device_model}` },
    { key: 'repair_status', label: 'Status', render: (row) => row.repair_status.replace(/_/g, ' ') },
    { key: 'service_charge_minor', label: 'Service charge', render: (row) => formatNaira(row.service_charge_minor) },
    { key: 'refund_due_minor', label: 'Refund due', render: (row) => formatNaira(row.refund_due_minor) },
];

export default function RepairReports({ report, filters, shops }: { report: Report; filters: ReportFilters; shops: ShopOption[] }) {
    return (
        <ReportShell title="Repair reports" description="Repair lifecycle, financial outcomes, technician work, and collection state." filters={filters} shops={shops}>
            <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4 mb-4">
                <ReportMetricCard label="Received" value={report.summary.received} />
                <ReportMetricCard label="Completed" value={report.summary.completed} />
                <ReportMetricCard label="Completion rate" value={`${report.summary.completion_rate}%`} />
                <ReportMetricCard label="Diagnosis revenue" valueMinor={report.summary.diagnosis_fee_minor} />
                <ReportMetricCard label="Repair revenue" valueMinor={report.summary.repair_revenue_minor} />
                <ReportMetricCard label="Cash collected" valueMinor={report.summary.cash_collected_minor} />
                <ReportMetricCard label="Down payments" valueMinor={report.summary.down_payment_minor} />
                <ReportMetricCard label="Service charges" valueMinor={report.summary.service_charge_minor} />
                <ReportMetricCard label="Refund due" valueMinor={report.summary.refund_due_minor} />
            </div>

            <div className="grid gap-4 lg:grid-cols-2 mb-4">
                <ReportPanel title="Repair trend"><TrendLine rows={report.trend} series={[{ key: 'received', label: 'Received' }, { key: 'completed', label: 'Completed' }]} /></ReportPanel>
                <ReportPanel title="Status breakdown"><DoughnutBreakdown rows={report.status_breakdown} /></ReportPanel>
                <ReportPanel title="Technician workload"><HorizontalBar rows={report.technicians} labelKey="name" valueKey="assigned" /></ReportPanel>
                <ReportPanel title="Collection state"><DoughnutBreakdown rows={report.collections} /></ReportPanel>
            </div>

            <ResponsiveDataTable
                columns={settlementColumns}
                rows={report.settlements}
                getRowKey={(row) => row.id}
                mobilePrimaryField="device_model"
                mobileStatusField="repair_status"
                mobileDetailFields={['service_charge_minor', 'refund_due_minor']}
            />
        </ReportShell>
    );
}
