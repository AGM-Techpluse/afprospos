import { Head } from '@inertiajs/react';
import AdminShell from '../../../Components/Admin/AdminShell';
import AdminButton from '../../../Components/Admin/AdminButton';
import { formatNaira } from '../../../lib/money';

type SaleItem = {
    sku_id: number;
    sku_code: string | null;
    product_name: string | null;
    quantity: number;
    unit_price_minor: number;
};

type Sale = {
    id: number;
    shop_id: number;
    invoice_number: string;
    payment_method: string;
    total_minor: number;
    subtotal_minor: number;
    discount_minor: number;
    created_at: string;
    items: SaleItem[];
};

/** BRD PAY-03: thermal/standard receipt printing at checkout — print:hidden on chrome, everything else stays plain and monospace-friendly. */
export default function SalesReceipt({ sale }: { sale: Sale }) {
    return (
        <AdminShell>
            <Head title={`Receipt — ${sale.invoice_number}`} />

            <div className="print:hidden mb-4">
                <AdminButton onClick={() => window.print()}>Print receipt</AdminButton>
            </div>

            <div className="max-w-sm mx-auto bg-admin-surface border border-admin-border rounded-admin-card p-6 font-mono text-sm">
                <div className="text-center mb-4">
                    <div className="font-bold text-admin-text">AfProsPos — Shop #{sale.shop_id}</div>
                    <div className="text-admin-text2 text-xs">{new Date(sale.created_at).toLocaleString()}</div>
                    <div className="text-admin-text2 text-xs">{sale.invoice_number}</div>
                </div>

                <div className="border-t border-dashed border-admin-border my-2" />

                {sale.items.map((item, index) => (
                    <div key={index} className="flex justify-between text-admin-text mb-1">
                        <span>
                            {item.quantity}x {item.product_name ?? item.sku_code ?? `SKU #${item.sku_id}`}
                        </span>
                        <span>{formatNaira(item.unit_price_minor * item.quantity)}</span>
                    </div>
                ))}

                <div className="border-t border-dashed border-admin-border my-2" />

                <div className="flex justify-between text-admin-text">
                    <span>Subtotal</span>
                    <span>{formatNaira(sale.subtotal_minor)}</span>
                </div>
                {sale.discount_minor > 0 && (
                    <div className="flex justify-between text-admin-text">
                        <span>Discount</span>
                        <span>-{formatNaira(sale.discount_minor)}</span>
                    </div>
                )}
                <div className="flex justify-between text-admin-text font-bold text-base mt-1">
                    <span>Total</span>
                    <span>{formatNaira(sale.total_minor)}</span>
                </div>

                <div className="border-t border-dashed border-admin-border my-2" />

                <div className="text-center text-admin-text2 text-xs capitalize">Paid via {sale.payment_method.replace('_', ' ')}</div>
                <div className="text-center text-admin-text3 text-xs mt-2">Thank you for your purchase.</div>
            </div>
        </AdminShell>
    );
}
