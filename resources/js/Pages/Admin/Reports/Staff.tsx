import { router } from '@inertiajs/react';
import ReportMetricCard from '../../../Features/Reports/ReportMetricCard';
import { DoughnutBreakdown, HorizontalBar } from '../../../Features/Reports/ReportCharts';
import { ReportPanel, ReportShell } from '../../../Features/Reports/ReportShell';
import { ReportFilters, ShopOption } from '../../../Features/Reports/types';
import AdminButton from '../../../Components/Admin/AdminButton';
import ResponsiveDataTable, { DataTableColumn } from '../../../Components/Tables/ResponsiveDataTable';
import { formatNaira } from '../../../lib/money';

type CashierRow = { staff_id: number; name: string; sales_count: number; revenue_minor: number };
type TechnicianRow = { staff_id: number; name: string; assigned: number; completed: number };
type CommissionRow = { staff_id: number; name: string; role_snapshot: string; entries_count: number; total_minor: number };
type Report = {
    summary: Record<string, number>;
    role_distribution: { label: string; value: number }[];
    cashiers: CashierRow[];
    technicians: TechnicianRow[];
    activity: { label: string; value: number }[];
};

const commissionColumns: DataTableColumn<CommissionRow>[] = [
    { key: 'name', label: 'Staff' },
    { key: 'role_snapshot', label: 'Role' },
    { key: 'entries_count', label: 'Entries' },
    { key: 'total_minor', label: 'Commission earned', render: (row) => formatNaira(row.total_minor) },
];

export default function StaffReports({
    report,
    commission_by_staff: commissionByStaff,
    filters,
    shops,
}: {
    report: Report;
    commission_by_staff: CommissionRow[];
    filters: ReportFilters;
    shops: ShopOption[];
}) {
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

            <h2 className="text-sm font-semibold text-admin-text mb-2">Commission earned by staff</h2>
            <ResponsiveDataTable
                columns={commissionColumns}
                rows={commissionByStaff}
                getRowKey={(row) => row.staff_id}
                mobilePrimaryField="name"
                mobileDetailFields={['role_snapshot', 'entries_count', 'total_minor']}
                renderActions={(row) => (
                    <AdminButton variant="neutral" onClick={() => router.visit(`/admin/commission?staff_id=${row.staff_id}`)}>
                        View
                    </AdminButton>
                )}
                emptyState={<p className="text-sm text-admin-text2 py-6 text-center">No commission earned in this period.</p>}
            />
        </ReportShell>
    );
}
