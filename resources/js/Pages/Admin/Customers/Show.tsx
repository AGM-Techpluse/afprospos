import { Head, router } from '@inertiajs/react';
import AdminShell from '../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../Components/Admin/AdminPageHead';
import AdminBadge from '../../../Components/Admin/AdminBadge';
import AdminButton from '../../../Components/Admin/AdminButton';
import AdminEmptyState from '../../../Components/Admin/AdminEmptyState';
import AdminPagination from '../../../Components/Admin/AdminPagination';
import AdminStatCard from '../../../Components/Admin/AdminStatCard';
import ResponsiveDataTable, { DataTableColumn } from '../../../Components/Tables/ResponsiveDataTable';
import { formatNaira } from '../../../lib/money';

type Customer = {
    id: number;
    name: string;
    email: string | null;
    phone: string;
    is_registered: boolean;
    created_at: string;
};

type OrderRow = {
    type: 'checkout' | 'sale';
    id: number;
    status: string;
    total_minor: number;
    invoice_number?: string;
    reservation_expires_at?: string;
    created_at: string;
};

type RepairRow = {
    id: number;
    device_make: string;
    device_model: string;
    repair_status: string;
    financial_status: string;
    labour_charge_minor: number;
    estimated_collection_date: string | null;
    created_at: string;
};

type Paginated<T> = { data: T[]; current_page: number; last_page: number; per_page: number; total: number };

interface CustomerShowProps {
    customer: Customer;
    orderSummary: { order_count: number; total_spent_minor: number };
    repairCount: number;
    orders: Paginated<OrderRow>;
    repairs: Paginated<RepairRow>;
}

const ORDER_COLUMNS: DataTableColumn<OrderRow>[] = [
    {
        key: 'invoice_number',
        label: 'Reference',
        render: (row) => row.type === 'sale' ? row.invoice_number : <span className="text-admin-text3">Open checkout</span>,
    },
    { key: 'total_minor', label: 'Total', render: (row) => formatNaira(row.total_minor) },
    {
        key: 'status',
        label: 'Status',
        render: (row) => <AdminBadge status={row.type === 'sale' ? 'success' : 'warning'}>{row.type === 'sale' ? 'Paid' : 'Open'}</AdminBadge>,
    },
    { key: 'created_at', label: 'Date', render: (row) => new Date(row.created_at).toLocaleDateString() },
];

const REPAIR_COLUMNS: DataTableColumn<RepairRow>[] = [
    { key: 'device_model', label: 'Device', render: (row) => `${row.device_make} ${row.device_model}` },
    { key: 'repair_status', label: 'Status', render: (row) => <AdminBadge status="neutral">{row.repair_status.replace(/_/g, ' ')}</AdminBadge> },
    { key: 'labour_charge_minor', label: 'Labour charge', render: (row) => formatNaira(row.labour_charge_minor) },
    { key: 'created_at', label: 'Received', render: (row) => new Date(row.created_at).toLocaleDateString() },
];

export default function CustomerShow({ customer, orderSummary, repairCount, orders, repairs }: CustomerShowProps) {
    function goToOrdersPage(page: number) {
        router.get(`/admin/customers/${customer.id}`, { orders_page: page }, { only: ['orders'], preserveState: true, preserveScroll: true });
    }

    function goToRepairsPage(page: number) {
        router.get(`/admin/customers/${customer.id}`, { repairs_page: page }, { only: ['repairs'], preserveState: true, preserveScroll: true });
    }

    return (
        <AdminShell>
            <Head title={customer.name} />

            <AdminPageHead
                title={customer.name}
                description={[customer.phone, customer.email].filter(Boolean).join(' · ')}
                backHref="/admin/customers"
                backLabel="Back to customers"
                actions={
                    <AdminBadge status={customer.is_registered ? 'success' : 'neutral'}>
                        {customer.is_registered ? 'Registered' : 'Not yet registered'}
                    </AdminBadge>
                }
            />

            <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                <AdminStatCard label="Orders" value={String(orderSummary.order_count)} />
                <AdminStatCard label="Total spent" value={formatNaira(orderSummary.total_spent_minor)} />
                <AdminStatCard label="Repairs" value={String(repairCount)} />
                <AdminStatCard label="Customer since" value={new Date(customer.created_at).toLocaleDateString()} />
            </div>

            <div className="mb-8">
                <h2 className="text-admin-md font-semibold text-admin-text mb-4">Orders</h2>
                <ResponsiveDataTable
                    columns={ORDER_COLUMNS}
                    rows={orders.data}
                    getRowKey={(row) => `${row.type}-${row.id}`}
                    mobilePrimaryField="invoice_number"
                    mobileStatusField="status"
                    mobileDetailFields={['total_minor', 'created_at']}
                    renderActions={(row) => (
                        <AdminButton
                            variant="neutral"
                            onClick={() =>
                                router.visit(
                                    row.type === 'sale' ? `/admin/sales/${row.id}` : `/admin/sales/checkout?checkout=${row.id}`,
                                )
                            }
                        >
                            {row.type === 'sale' ? 'View' : 'Resume checkout'}
                        </AdminButton>
                    )}
                    emptyState={<AdminEmptyState icon="receipt" title="No orders yet" description="This customer hasn't made a purchase yet." />}
                    footer={
                        orders.total > 0 ? (
                            <AdminPagination currentPage={orders.current_page} totalItems={orders.total} pageSize={orders.per_page} onPageChange={goToOrdersPage} />
                        ) : undefined
                    }
                />
            </div>

            <div>
                <h2 className="text-admin-md font-semibold text-admin-text mb-4">Repairs</h2>
                <ResponsiveDataTable
                    columns={REPAIR_COLUMNS}
                    rows={repairs.data}
                    getRowKey={(row) => row.id}
                    mobilePrimaryField="device_model"
                    mobileStatusField="repair_status"
                    mobileDetailFields={['labour_charge_minor', 'created_at']}
                    renderActions={(row) => (
                        <AdminButton variant="neutral" onClick={() => router.visit(`/admin/repairs/${row.id}`)}>
                            View
                        </AdminButton>
                    )}
                    emptyState={<AdminEmptyState icon="tools" title="No repairs yet" description="This customer hasn't booked a repair yet." />}
                    footer={
                        repairs.total > 0 ? (
                            <AdminPagination currentPage={repairs.current_page} totalItems={repairs.total} pageSize={repairs.per_page} onPageChange={goToRepairsPage} />
                        ) : undefined
                    }
                />
            </div>
        </AdminShell>
    );
}
