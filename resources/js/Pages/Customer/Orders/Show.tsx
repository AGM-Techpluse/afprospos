import { Head } from '@inertiajs/react';
import CustomerShell from '../../../Components/Customer/CustomerShell';
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

    return (
        <CustomerShell>
            <Head title={title} />

            <div className="customer-content-grid py-4 sm:py-0">
                <h1 className="font-bold text-customer-lg text-customer-text mb-1">{title}</h1>
                <p className="text-customer-caption text-customer-text2 mb-4">
                    {type === 'sale' ? 'Completed purchase' : 'Awaiting payment — visit a shop or contact us to complete this checkout.'}
                </p>

                <div className="bg-white rounded-customer-card shadow-customer-soft p-4">
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

                    <div className="flex items-center justify-between pt-3 mt-2">
                        <span className="font-bold text-customer-text">Total</span>
                        <span className="font-bold text-customer-text">{formatNaira(order.total_minor)}</span>
                    </div>
                </div>
            </div>
        </CustomerShell>
    );
}
