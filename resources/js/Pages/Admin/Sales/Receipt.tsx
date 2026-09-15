import { Head, usePage } from '@inertiajs/react';
import AdminShell from '../../../Components/Admin/AdminShell';
import AdminButton from '../../../Components/Admin/AdminButton';
import Icon from '../../../Components/Icons/Icon';
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

const PAYMENT_METHOD_LABELS: Record<string, string> = {
    cash: 'Cash',
    pos_terminal: 'POS terminal',
    bank_transfer: 'Bank transfer',
    in_app: 'In-app',
};

/**
 * BRD PAY-03: printable receipt at checkout. Only the `.print-area` strip
 * survives window.print() (see the global rule in app.css) — the
 * AdminShell chrome and the actions row above it never reach paper.
 * Deliberately styled like an actual till slip (narrow column, cream
 * paper tone, monospace, dashed rules, torn top/bottom edges, footer
 * barcode) rather than a fintech "payment confirmed" app card — a
 * receipt should read as paper, not as a UI screen.
 */
export default function SalesReceipt({ sale }: { sale: Sale }) {
    const { app_name } = usePage().props as { app_name?: string };
    const paymentLabel = PAYMENT_METHOD_LABELS[sale.payment_method] ?? sale.payment_method.replace(/_/g, ' ');
    const shopName = app_name ?? 'AfProsPos';

    return (
        <AdminShell>
            <Head title={`Receipt — ${sale.invoice_number}`} />

            <div className="print:hidden mb-6 max-w-[340px] mx-auto flex items-center justify-between">
                <a href="/admin/sales" className="text-sm font-semibold text-admin-blue inline-flex items-center gap-1">
                    <Icon name="arrow-left" />
                    Back to sales
                </a>
                <AdminButton onClick={() => window.print()}>
                    <Icon name="printer" className="mr-1.5" />
                    Print receipt
                </AdminButton>
            </div>

            {/* Narrow on screen (a real till slip reads as a narrow strip), but that
                same width leaves a printed A4/A5 sheet mostly blank — widen it only
                for print via the print: variant rather than compromising the on-screen look. */}
            <div className="print-area max-w-[340px] print:max-w-[480px] mx-auto shadow-admin-card">
                <div className="receipt-torn-edge receipt-torn-edge-top" aria-hidden="true" />

                <div className="bg-admin-paper px-5 pt-5 pb-6 font-mono text-admin-text">
                    <div className="text-center mb-3">
                        <div className="text-base font-bold uppercase tracking-wide">{shopName}</div>
                        <div className="text-xs text-admin-text2">Shop #{sale.shop_id}</div>
                    </div>

                    <div className="border-t border-dashed border-admin-border-strong my-3" />

                    <div className="text-xs text-admin-text2 flex justify-between">
                        <span>Date</span>
                        <span>{new Date(sale.created_at).toLocaleString()}</span>
                    </div>
                    <div className="text-xs text-admin-text2 flex justify-between mt-1">
                        <span>Invoice</span>
                        <span className="font-semibold text-admin-text">{sale.invoice_number}</span>
                    </div>

                    <div className="border-t border-dashed border-admin-border-strong my-3" />

                    {sale.items.map((item, index) => (
                        <div key={index} className="text-sm mb-2">
                            <div className="flex justify-between gap-2">
                                <span className="truncate">{item.product_name ?? item.sku_code ?? `SKU #${item.sku_id}`}</span>
                                <span className="shrink-0">{formatNaira(item.unit_price_minor * item.quantity)}</span>
                            </div>
                            <div className="text-xs text-admin-text3">
                                {item.quantity} × {formatNaira(item.unit_price_minor)}
                            </div>
                        </div>
                    ))}

                    <div className="border-t border-dashed border-admin-border-strong my-3" />

                    <div className="text-sm flex justify-between">
                        <span className="text-admin-text2">Subtotal</span>
                        <span>{formatNaira(sale.subtotal_minor)}</span>
                    </div>
                    {sale.discount_minor > 0 && (
                        <div className="text-sm flex justify-between mt-1">
                            <span className="text-admin-text2">Discount</span>
                            <span>-{formatNaira(sale.discount_minor)}</span>
                        </div>
                    )}
                    <div className="flex justify-between text-base font-bold mt-2 pt-2 border-t border-admin-border-strong">
                        <span>TOTAL</span>
                        <span>{formatNaira(sale.total_minor)}</span>
                    </div>

                    <div className="flex justify-center my-4">
                        <div className="border-2 border-admin-green text-admin-green text-xs font-bold uppercase tracking-widest px-4 py-1 -rotate-6 rounded-admin-badge">
                            Paid — {paymentLabel}
                        </div>
                    </div>

                    <div className="border-t border-dashed border-admin-border-strong my-3" />

                    <p className="text-center text-xs text-admin-text2">Thank you for shopping with {shopName}.</p>
                    <p className="text-center text-[10px] text-admin-text3 mt-1">Keep this receipt as proof of purchase.</p>

                    <div className="receipt-barcode mt-4" aria-hidden="true" />
                    <div className="text-center text-[10px] tracking-[0.3em] text-admin-text3 mt-1">{sale.invoice_number}</div>
                </div>

                <div className="receipt-torn-edge" aria-hidden="true" />
            </div>
        </AdminShell>
    );
}
