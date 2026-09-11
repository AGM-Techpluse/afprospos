import { Head, router } from '@inertiajs/react';
import CustomerShell from '../../../Components/Customer/CustomerShell';
import CustomerListItem from '../../../Components/Customer/CustomerListItem';
import CustomerEmptyState from '../../../Components/Customer/CustomerEmptyState';
import { formatNaira } from '../../../lib/money';

type OrderRow = {
    type: 'checkout' | 'sale';
    id: number;
    status: string;
    total_minor: number;
    invoice_number?: string;
    reservation_expires_at?: string;
    created_at: string;
};

type Paginated<T> = { data: T[]; current_page: number; last_page: number; per_page: number; total: number };

/** BRD SALE-03: read-only order visibility ("payable in the customer's dashboard") — no payment-initiation UI here, that's Payments' (Phase 5) job. */
export default function OrdersIndex({ orders }: { orders: Paginated<OrderRow> }) {
    return (
        <CustomerShell>
            <Head title="Orders" />

            <div className="customer-content-grid py-4 sm:py-0">
                <h1 className="font-bold text-customer-lg text-customer-text mb-4">Your orders</h1>

                {orders.data.length === 0 ? (
                    <CustomerEmptyState
                        icon="bag"
                        description="No orders yet — once you make a purchase, it'll show up here."
                        className="bg-white rounded-customer-card shadow-customer-soft"
                    />
                ) : (
                    orders.data.map((order) => (
                        <CustomerListItem
                            key={`${order.type}-${order.id}`}
                            icon={order.type === 'sale' ? 'receipt' : 'hourglass-split'}
                            title={order.invoice_number ?? `Checkout #${order.id}`}
                            subtitle={order.type === 'sale' ? 'Completed' : 'Awaiting payment'}
                            amount={formatNaira(order.total_minor)}
                            details={[{ label: 'Date', value: new Date(order.created_at).toLocaleString() }]}
                            actions={[
                                <button
                                    key="view"
                                    type="button"
                                    onClick={() =>
                                        router.visit(
                                            order.type === 'sale'
                                                ? `/customer/orders/sales/${order.id}`
                                                : `/customer/orders/checkouts/${order.id}`,
                                        )
                                    }
                                    className="text-customer-blue font-semibold text-customer-caption"
                                >
                                    View details
                                </button>,
                            ]}
                        />
                    ))
                )}
            </div>
        </CustomerShell>
    );
}
