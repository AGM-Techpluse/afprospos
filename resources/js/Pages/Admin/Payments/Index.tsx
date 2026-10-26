import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminShell from '../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../Components/Admin/AdminPageHead';
import AdminButton from '../../../Components/Admin/AdminButton';
import AdminBulkActionBar from '../../../Components/Admin/AdminBulkActionBar';
import AdminBadge, { AdminBadgeStatus } from '../../../Components/Admin/AdminBadge';
import AdminEmptyState from '../../../Components/Admin/AdminEmptyState';
import AdminPagination from '../../../Components/Admin/AdminPagination';
import AdminSelect from '../../../Components/Admin/Forms/AdminSelect';
import ResponsiveDataTable, { DataTableColumn } from '../../../Components/Tables/ResponsiveDataTable';
import { formatNaira } from '../../../lib/money';

type PaymentRow = {
    id: number;
    payable_type: string;
    payable_id: number;
    purpose: string;
    method: string;
    amount_minor: number;
    status: string;
    provider_reference: string | null;
    created_at: string;
    source_label: string;
    customer_name: string | null;
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
    partially_refunded: 'warning',
    refunded: 'info',
    exception: 'danger',
};

const STATUS_LABEL: Record<string, string> = {
    pending: 'Pending',
    payment_pending_confirmation: 'Awaiting confirmation',
    confirmed: 'Confirmed',
    disputed: 'Disputed',
    partially_refunded: 'Partially refunded',
    refunded: 'Refunded',
    exception: 'Exception',
};

const PURPOSE_LABEL: Record<string, string> = {
    sale_payment: 'Sale payment',
    repair_down_payment: 'Repair down payment',
    repair_final_payment: 'Repair final payment',
    repair_diagnosis_fee: 'Diagnosis fee',
};

const COLUMNS: DataTableColumn<PaymentRow>[] = [
    { key: 'purpose', label: 'Purpose', render: (row) => PURPOSE_LABEL[row.purpose] ?? row.purpose.replace(/_/g, ' ') },
    { key: 'source_label', label: 'For' },
    { key: 'customer_name', label: 'Customer', render: (row) => row.customer_name ?? <span className="text-admin-text3">Walk-in</span> },
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
    const [selectedIds, setSelectedIds] = useState<(string | number)[]>([]);

    function exportMatchingFilters() {
        const params = new URLSearchParams();
        if (filters.status) params.set('status', filters.status);
        if (filters.method) params.set('method', filters.method);
        window.location.href = `/admin/payments/export?${params.toString()}`;
    }

    function exportSelected() {
        const params = new URLSearchParams();
        selectedIds.forEach((id) => params.append('ids[]', String(id)));
        window.location.href = `/admin/payments/export?${params.toString()}`;
    }

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

            <AdminPageHead
                title="Payments"
                description="Reconciliation queue — confirm, dispute, or refund a payment."
                actions={<AdminButton variant="neutral" onClick={exportMatchingFilters}>Export all matching filters</AdminButton>}
            />

            <AdminBulkActionBar
                selectedCount={selectedIds.length}
                onClear={() => setSelectedIds([])}
                actions={
                    <AdminButton type="button" variant="neutral" onClick={exportSelected}>
                        Export selected ({selectedIds.length})
                    </AdminButton>
                }
            />

            <ResponsiveDataTable
                columns={COLUMNS}
                rows={payments.data}
                getRowKey={(row) => row.id}
                mobilePrimaryField="method"
                mobileStatusField="status"
                mobileDetailFields={['customer_name', 'payable_type', 'amount_minor', 'created_at']}
                selectable
                selectedIds={selectedIds}
                onSelectionChange={setSelectedIds}
                toolbar={
                    <div className="flex gap-2">
                        <AdminSelect aria-label="Filter by status" value={filters.status} onChange={(e) => applyFilter({ status: e.target.value })} containerClassName="mb-0">
                            <option value="">All statuses</option>
                            {Object.entries(STATUS_LABEL).map(([value, label]) => (
                                <option key={value} value={value}>
                                    {label}
                                </option>
                            ))}
                        </AdminSelect>
                        <AdminSelect aria-label="Filter by method" value={filters.method} onChange={(e) => applyFilter({ method: e.target.value })} containerClassName="mb-0">
                            <option value="">All methods</option>
                            <option value="cash">Cash</option>
                            <option value="pos_terminal">POS terminal</option>
                            <option value="bank_transfer">Bank transfer</option>
                            <option value="in_app">In-app</option>
                        </AdminSelect>
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
