import { Head, router } from '@inertiajs/react';
import AdminShell from '../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../Components/Admin/AdminPageHead';
import AdminButton from '../../../Components/Admin/AdminButton';
import AdminBadge, { AdminBadgeStatus } from '../../../Components/Admin/AdminBadge';
import AdminEmptyState from '../../../Components/Admin/AdminEmptyState';
import AdminPagination from '../../../Components/Admin/AdminPagination';
import ResponsiveDataTable, { DataTableColumn } from '../../../Components/Tables/ResponsiveDataTable';
import { formatNaira } from '../../../lib/money';

type PaymentRow = {
    id: number;
    payable_type: string;
    payable_id: number;
    method: string;
    amount_minor: number;
    status: string;
    provider_reference: string | null;
    created_at: string;
};

type Paginated<T> = { data: T[]; current_page: number; last_page: number; per_page: number; total: number };

interface PaymentsIndexProps {
    payments: Paginated<PaymentRow>;
    filters: { status: string; method: string };
}

const STATUS_BADGE: Record<string, AdminBadgeStatus> = {
    pending: 'neutral',
    payment_pending_confirmation: 'warning',
    confirmed: 'success',
    disputed: 'danger',
    refunded: 'info',
    exception: 'danger',
};

const STATUS_LABEL: Record<string, string> = {
    pending: 'Pending',
    payment_pending_confirmation: 'Awaiting confirmation',
    confirmed: 'Confirmed',
    disputed: 'Disputed',
    refunded: 'Refunded',
    exception: 'Exception',
};

const COLUMNS: DataTableColumn<PaymentRow>[] = [
    { key: 'id', label: 'ID', render: (row) => `#${row.id}` },
    { key: 'payable_type', label: 'For', render: (row) => `${row.payable_type.replace('_', ' ')} #${row.payable_id}` },
    { key: 'method', label: 'Method' },
    { key: 'amount_minor', label: 'Amount', render: (row) => formatNaira(row.amount_minor) },
    {
        key: 'status',
        label: 'Status',
        render: (row) => <AdminBadge status={STATUS_BADGE[row.status] ?? 'neutral'}>{STATUS_LABEL[row.status] ?? row.status}</AdminBadge>,
    },
    { key: 'created_at', label: 'Date', render: (row) => new Date(row.created_at).toLocaleString() },
];

export default function PaymentsIndex({ payments, filters }: PaymentsIndexProps) {
    function applyFilter(next: Partial<{ status: string; method: string }>) {
        router.get(
            '/admin/payments',
            { status: next.status ?? filters.status ?? undefined, method: next.method ?? filters.method ?? undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function goToPage(page: number) {
        router.get('/admin/payments', { status: filters.status || undefined, method: filters.method || undefined, page }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    return (
        <AdminShell>
            <Head title="Payments" />

            <AdminPageHead title="Payments" description="Reconciliation queue — confirm, dispute, or refund a payment." />

            <ResponsiveDataTable
                columns={COLUMNS}
                rows={payments.data}
                getRowKey={(row) => row.id}
                mobilePrimaryField="method"
                mobileStatusField="status"
                mobileDetailFields={['payable_type', 'amount_minor', 'created_at']}
                toolbar={
                    <div className="flex gap-2">
                        <select
                            value={filters.status}
                            onChange={(e) => applyFilter({ status: e.target.value })}
                            className="border border-admin-border rounded-admin-button px-2 py-1.5 text-xs bg-admin-bg outline-none focus:border-admin-blue focus:ring-2 focus:ring-admin-focus"
                        >
                            <option value="">All statuses</option>
                            {Object.entries(STATUS_LABEL).map(([value, label]) => (
                                <option key={value} value={value}>
                                    {label}
                                </option>
                            ))}
                        </select>
                        <select
                            value={filters.method}
                            onChange={(e) => applyFilter({ method: e.target.value })}
                            className="border border-admin-border rounded-admin-button px-2 py-1.5 text-xs bg-admin-bg outline-none focus:border-admin-blue focus:ring-2 focus:ring-admin-focus"
                        >
                            <option value="">All methods</option>
                            <option value="cash">Cash</option>
                            <option value="pos_terminal">POS terminal</option>
                            <option value="bank_transfer">Bank transfer</option>
                            <option value="in_app">In-app</option>
                        </select>
                    </div>
                }
                renderActions={(row) => (
                    <AdminButton variant="neutral" onClick={() => router.visit(`/admin/payments/${row.id}`)}>
                        View
                    </AdminButton>
                )}
                emptyState={
                    <AdminEmptyState icon="credit-card-2-front" title="No payments" description="Payments will show up here as sales are completed." />
                }
                footer={
                    payments.total > 0 ? (
                        <AdminPagination currentPage={payments.current_page} totalItems={payments.total} pageSize={payments.per_page} onPageChange={goToPage} />
                    ) : undefined
                }
            />
        </AdminShell>
    );
}
