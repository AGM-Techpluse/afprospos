import { Head } from '@inertiajs/react';
import CustomerShell from '../../../Components/Customer/CustomerShell';
import CustomerDetailCard from '../../../Components/Customer/CustomerDetailCard';
import Icon from '../../../Components/Icons/Icon';
import { formatNaira } from '../../../lib/money';

type OrderItem = {
    sku_code?: string | null;
    product_name?: string | null;
    sku_id: number;
    quantity: number;
    unit_price_minor: number;
};

type Order = {
    id: number;
    status?: string;
    invoice_number?: string;
    total_minor: number;
    subtotal_minor?: number;
    discount_minor?: number;
    reservation_expires_at?: string;
    created_at: string;
    items?: OrderItem[];
};

interface OrdersShowProps {
    type: 'checkout' | 'sale';
    order: Order;
}

export default function OrdersShow({ type, order }: OrdersShowProps) {
    const title = type === 'sale' ? (order.invoice_number ?? `Sale #${order.id}`) : `Checkout #${order.id}`;
    const itemCount = (order.items ?? []).reduce((total, item) => total + item.quantity, 0);

    return (
        <CustomerShell>
            <Head title={title} />

            {/* One wrapping div = one grid item, so this column stays in customer-content-grid's first track. */}
            <div className="customer-content-grid py-4 sm:py-0">
                <div>
                    <h1 className="font-customer-display font-extrabold text-customer-2xl text-customer-text mb-4">{title}</h1>

                    <CustomerDetailCard
                        icon={type === 'sale' ? 'receipt' : 'hourglass-split'}
                        title={title}
                        subtitle={new Date(order.created_at).toLocaleDateString()}
                        badge={{ label: type === 'sale' ? 'Completed' : 'Awaiting payment', tone: type === 'sale' ? 'success' : 'warning' }}
                        details={[
                            { label: 'Items', value: `${itemCount} item${itemCount === 1 ? '' : 's'}` },
                            { label: 'Date', value: new Date(order.created_at).toLocaleDateString() },
                        ]}
                        amountLabel="Total amount"
                        amount={formatNaira(order.total_minor)}
                        actions={
                            <>
                                {type === 'checkout' && order.status === 'open' && (
                                    <a
                                        href={`/customer/payments/checkouts/${order.id}`}
                                        className="inline-flex items-center gap-1.5 rounded-customer-pill bg-customer-blue text-white font-semibold text-customer-caption px-3 py-1.5 print:hidden"
                                    >
                                        <Icon name="wallet2" />
                                        Pay now
                                    </a>
                                )}
                                {type === 'sale' && (
                                    <>
                                        <button
                                            type="button"
                                            onClick={() => window.print()}
                                            className="inline-flex items-center gap-1.5 rounded-customer-pill bg-customer-blue text-white font-semibold text-customer-caption px-3 py-1.5 print:hidden"
                                        >
                                            <Icon name="printer" />
                                            Print
                                        </button>
                                        <a
                                            href={`/customer/warranty/claims/create?sale=${order.id}`}
                                            className="inline-flex items-center gap-1.5 rounded-customer-pill bg-customer-blue text-white font-semibold text-customer-caption px-3 py-1.5 print:hidden"
                                        >
                                            <Icon name="shield-exclamation" />
                                            File a claim
                                        </a>
                                        <a
                                            href={`/customer/warranty/returns/create?sale=${order.id}`}
                                            className="inline-flex items-center gap-1.5 rounded-customer-pill bg-customer-blue text-white font-semibold text-customer-caption px-3 py-1.5 print:hidden"
                                        >
                                            <Icon name="arrow-counterclockwise" />
                                            Start a return
                                        </a>
                                    </>
                                )}
                            </>
                        }
                    />

                    {(order.items ?? []).length > 0 && (
                        <div className="bg-white rounded-customer-card shadow-customer-soft p-4 mt-4">
                            <h2 className="font-bold text-customer-text mb-2">Items</h2>
                            {(order.items ?? []).map((item, index) => (
                                <div key={index} className="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                                    <span className="text-customer-text text-customer-caption">
                                        {item.quantity}x {item.product_name ?? item.sku_code ?? `SKU #${item.sku_id}`}
                                    </span>
                                    <span className="text-customer-text font-semibold text-customer-caption">
                                        {formatNaira(item.unit_price_minor * item.quantity)}
                                    </span>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </CustomerShell>
    );
}
