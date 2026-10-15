import { useState } from 'react';
import { router } from '@inertiajs/react';
import ReportMetricCard from '../../../Features/Reports/ReportMetricCard';
import { DoughnutBreakdown, HorizontalBar, TrendLine } from '../../../Features/Reports/ReportCharts';
import { ReportPanel, ReportShell } from '../../../Features/Reports/ReportShell';
import { ReportFilters, ShopOption } from '../../../Features/Reports/types';
import AdminBadge, { AdminBadgeStatus } from '../../../Components/Admin/AdminBadge';
import AdminButton from '../../../Components/Admin/AdminButton';
import AdminPagination from '../../../Components/Admin/AdminPagination';
import ResponsiveDataTable, { DataTableColumn } from '../../../Components/Tables/ResponsiveDataTable';
import { formatNaira } from '../../../lib/money';

type Report = {
    summary: Record<string, number>;
    status_breakdown: { label: string; value: number }[];
    trend: { date: string; received: number; completed: number }[];
    technicians: { staff_id: number; name: string; assigned: number; completed: number; failed: number }[];
    collections: { label: string; value: number }[];
};
type RepairRow = {
    id: number;
    customer_name: string | null;
    device_make: string;
    device_model: string;
    technician_staff_id: number | null;
    repair_cost_minor: number | null;
    repair_status: string;
    financial_status: string;
    created_at: string;
};
type Paginated<T> = { data: T[]; current_page: number; last_page: number; per_page: number; total: number };

const STATUS_BADGE: Record<string, AdminBadgeStatus> = {
    received: 'neutral',
    diagnosing: 'info',
    awaiting_authorization: 'warning',
    payment_overdue: 'warning',
    expired_cancelled: 'danger',
    awaiting_parts: 'info',
    in_progress: 'info',
    qc_pending: 'info',
    handover: 'info',
    cancelled: 'neutral',
    completed: 'success',
    unrepairable: 'danger',
    failed_requires_resolution: 'danger',
};

const repairColumns: DataTableColumn<RepairRow>[] = [
    { key: 'device_model', label: 'Work order', render: (row) => `${row.device_make} ${row.device_model}` },
    { key: 'customer_name', label: 'Customer', render: (row) => row.customer_name ?? 'Walk-in' },
    {
        key: 'repair_status',
        label: 'Status',
        render: (row) => <AdminBadge status={STATUS_BADGE[row.repair_status] ?? 'neutral'}>{row.repair_status.replace(/_/g, ' ')}</AdminBadge>,
    },
    { key: 'repair_cost_minor', label: 'Repair cost', render: (row) => (row.repair_cost_minor !== null ? formatNaira(row.repair_cost_minor) : '—') },
    { key: 'created_at', label: 'Received', render: (row) => new Date(row.created_at).toLocaleDateString() },
];

export default function RepairReports({
    report,
    repair_list: repairList,
    filters,
    shops,
}: {
    report: Report;
    repair_list: Paginated<RepairRow>;
    filters: ReportFilters;
    shops: ShopOption[];
}) {
    const [status, setStatus] = useState('');

    function reload(next: { status?: string; page?: number }) {
        router.get(
            '/admin/reports/repairs',
            {
                range: filters.range,
                start_date: filters.start_date,
                end_date: filters.end_date,
                shop_id: filters.shop_id ?? '',
                status: next.status ?? status,
                page: next.page ?? 1,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

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

            <h2 className="text-sm font-semibold text-admin-text mb-2">Repairs in this period</h2>
            <ResponsiveDataTable
                columns={repairColumns}
                rows={repairList.data}
                getRowKey={(row) => row.id}
                mobilePrimaryField="device_model"
                mobileStatusField="repair_status"
                mobileDetailFields={['customer_name', 'repair_cost_minor', 'created_at']}
                toolbar={
                    <select
                        value={status}
                        onChange={(e) => {
                            setStatus(e.target.value);
                            reload({ status: e.target.value, page: 1 });
                        }}
                        className="border border-admin-border rounded-admin-button px-2.5 py-1.5 text-xs bg-admin-bg"
                    >
                        <option value="">All statuses</option>
                        {Object.keys(STATUS_BADGE).map((value) => (
                            <option key={value} value={value}>{value.replace(/_/g, ' ')}</option>
                        ))}
                    </select>
                }
                renderActions={(row) => (
                    <AdminButton variant="neutral" onClick={() => router.visit(`/admin/repairs/${row.id}`)}>
                        View
                    </AdminButton>
                )}
                footer={
                    repairList.total > 0 ? (
                        <AdminPagination currentPage={repairList.current_page} totalItems={repairList.total} pageSize={repairList.per_page} onPageChange={(page) => reload({ page })} />
                    ) : undefined
                }
            />
        </ReportShell>
    );
}
