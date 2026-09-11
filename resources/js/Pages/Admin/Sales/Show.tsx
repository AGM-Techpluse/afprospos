import { Head, router } from '@inertiajs/react';
import AdminShell from '../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../Components/Admin/AdminPageHead';
import AdminButton from '../../../Components/Admin/AdminButton';
import { formatNaira } from '../../../lib/money';

type SaleItem = {
    sku_id: number;
    sku_code: string | null;
    product_name: string | null;
    inventory_item_id: number | null;
    quantity: number;
    unit_price_minor: number;
};

type SaleAdjustment = { type: string; amount_minor: number };

type Sale = {
    id: number;
    shop_id: number;
    customer_id: number | null;
    invoice_number: string;
    payment_method: string;
    payment_reference: string | null;
    total_minor: number;
    subtotal_minor: number;
    discount_minor: number;
    created_at: string;
    items: SaleItem[];
    adjustments: SaleAdjustment[];
};

export default function SalesShow({ sale }: { sale: Sale }) {
    return (
        <AdminShell>
            <Head title={sale.invoice_number} />

            <AdminPageHead
                title={sale.invoice_number}
                description={new Date(sale.created_at).toLocaleString()}
                actions={
                    <AdminButton variant="neutral" onClick={() => router.visit(`/admin/sales/${sale.id}/receipt`)}>
                        View receipt
                    </AdminButton>
                }
            />

            <div className="bg-admin-surface border border-admin-border rounded-admin-card p-6 max-w-2xl">
                <dl className="grid grid-cols-2 gap-y-2 text-sm mb-6">
                    <dt className="text-admin-text2">Customer</dt>
                    <dd className="text-admin-text text-right">{sale.customer_id ? `#${sale.customer_id}` : 'Walk-in'}</dd>
                    <dt className="text-admin-text2">Payment method</dt>
                    <dd className="text-admin-text text-right capitalize">{sale.payment_method.replace('_', ' ')}</dd>
                    {sale.payment_reference && (
                        <>
                            <dt className="text-admin-text2">Reference</dt>
                            <dd className="text-admin-text text-right">{sale.payment_reference}</dd>
                        </>
                    )}
                </dl>

                <table className="w-full text-sm mb-4">
                    <thead>
                        <tr className="text-left text-admin-text2 text-xs">
                            <th className="pb-2">Item</th>
                            <th className="pb-2 text-right">Qty</th>
                            <th className="pb-2 text-right">Unit price</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-admin-border">
                        {sale.items.map((item, index) => (
                            <tr key={index}>
                                <td className="py-2 text-admin-text">
                                    {item.product_name ?? `SKU #${item.sku_id}`}
                                    {item.inventory_item_id && <span className="text-admin-text3 text-xs block">IMEI-tracked unit</span>}
                                </td>
                                <td className="py-2 text-right text-admin-text">{item.quantity}</td>
                                <td className="py-2 text-right text-admin-text">{formatNaira(item.unit_price_minor)}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>

                <div className="border-t border-admin-border pt-3 space-y-1 text-sm">
                    <div className="flex justify-between">
                        <span className="text-admin-text2">Subtotal</span>
                        <span className="text-admin-text">{formatNaira(sale.subtotal_minor)}</span>
                    </div>
                    {sale.discount_minor > 0 && (
                        <div className="flex justify-between">
                            <span className="text-admin-text2">Discount</span>
                            <span className="text-admin-text">-{formatNaira(sale.discount_minor)}</span>
                        </div>
                    )}
                    <div className="flex justify-between font-semibold text-base">
                        <span className="text-admin-text">Total</span>
                        <span className="text-admin-text">{formatNaira(sale.total_minor)}</span>
                    </div>
                </div>
            </div>
        </AdminShell>
    );
}
