import { Head, router } from '@inertiajs/react';
import CustomerShell from '../../../Components/Customer/CustomerShell';
import CustomerListItem from '../../../Components/Customer/CustomerListItem';
import CustomerEmptyState from '../../../Components/Customer/CustomerEmptyState';
import CustomerQuickActions, { CustomerQuickAction } from '../../../Components/Customer/CustomerQuickActions';
import { formatNaira } from '../../../lib/money';

const ORDERS_QUICK_ACTIONS: CustomerQuickAction[] = [
    { key: 'buy-phone', label: 'Buy a phone', icon: 'bag-check', bg: 'bg-customer-blue sm:bg-customer-blue-soft', text: 'text-white sm:text-customer-blue', border: 'border-customer-blue/40' },
    { key: 'claim-warranty', label: 'File a warranty claim', icon: 'shield-exclamation', bg: 'bg-customer-green sm:bg-customer-green-soft', text: 'text-white sm:text-customer-green', border: 'border-customer-green/40', href: '/customer/warranty/claims/create' },
    { key: 'refer', label: 'Refer a friend', icon: 'gift', bg: 'bg-customer-yellow sm:bg-customer-yellow-soft', text: 'text-customer-yellow-text', border: 'border-customer-yellow-text/40' },
];

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

            {/* One wrapping div = one grid item, so this column stays in customer-content-grid's first track. */}
            <div className="customer-content-grid py-4 sm:py-0">
                <div>
                    <h1 className="font-customer-display font-extrabold text-customer-2xl text-customer-text mb-4">Your orders</h1>

                    <CustomerQuickActions actions={ORDERS_QUICK_ACTIONS} gridClassName="grid-cols-2 sm:grid-cols-3" className="mb-6" />

                    {orders.data.length === 0 ? (
                        <CustomerEmptyState
                            icon="bag"
                            description="No orders yet — once you make a purchase, it'll show up here."
                            className="bg-white rounded-customer-card shadow-customer-soft"
                        />
                    ) : (
                        <div className="bg-white rounded-customer-card shadow-customer-soft px-4 overflow-hidden">
                            {orders.data.map((order) => (
                                <CustomerListItem
                                    key={`${order.type}-${order.id}`}
                                    icon={order.type === 'sale' ? 'receipt' : 'hourglass-split'}
                                    title={order.invoice_number ?? `Checkout #${order.id}`}
                                    subtitle={order.type === 'sale' ? 'Completed' : 'Awaiting payment'}
                                    amount={formatNaira(order.total_minor)}
                                    bare
                                    details={[{ label: 'Date', value: new Date(order.created_at).toLocaleString() }]}
                                    actions={[
                                        ...(order.type === 'checkout' && order.status === 'open'
                                            ? [
                                                  <button
                                                      key="pay"
                                                      type="button"
                                                      onClick={() => router.visit(`/customer/payments/checkouts/${order.id}`)}
                                                      className="text-customer-green font-semibold text-customer-caption"
                                                  >
                                                      Pay now
                                                  </button>,
                                              ]
                                            : []),
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
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </CustomerShell>
    );
}
