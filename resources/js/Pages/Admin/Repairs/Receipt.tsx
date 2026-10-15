import { Head, usePage } from '@inertiajs/react';
import AdminShell from '../../../Components/Admin/AdminShell';
import AdminButton from '../../../Components/Admin/AdminButton';
import Icon from '../../../Components/Icons/Icon';
import FaultOriginBadge from '../../../Features/Repairs/FaultOriginBadge';
import { formatNaira } from '../../../lib/money';

type FixedFault = { id: number; device_problem_tag_id: number | null; common_component_id: number | null; label: string };

type Repair = {
    id: number;
    shop_id: number;
    customer_name: string | null;
    walk_in_name: string | null;
    walk_in_phone: string | null;
    device_make: string;
    device_model: string;
    device_imei_serial: string | null;
    labour_charge_minor: number | null;
    repair_cost_minor: number | null;
    quote_total_minor: number | null;
    diagnosis_fee: { amount_minor: number; paid: boolean } | null;
    down_payment_paid_minor: number;
    final_payment_paid_minor: number;
    final_payment_method: string | null;
    financial_status: string;
    fixed_faults: FixedFault[];
    created_at: string;
};

type Shop = { id: number; name: string } | null;

const PAYMENT_METHOD_LABELS: Record<string, string> = {
    cash: 'Cash',
    pos_terminal: 'POS terminal',
    bank_transfer: 'Bank transfer',
    in_app: 'In-app',
};

/**
 * Printable repair receipt, same mechanism/skin as Sales/Receipt.tsx (a
 * till slip, not a fintech "payment confirmed" card) — only the
 * `.print-area` strip survives window.print(), see the global rule in
 * app.css. A repair's "reference number" is its own job id (repairs don't
 * have an invoice-number sequence like Sales does).
 */
export default function RepairReceipt({ repair, shop }: { repair: Repair; shop: Shop }) {
    const { app_name } = usePage().props as { app_name?: string };
    const shopName = app_name ?? 'AfProsPos';
    const totalPaidMinor = repair.down_payment_paid_minor + repair.final_payment_paid_minor + (repair.diagnosis_fee?.paid ? repair.diagnosis_fee.amount_minor : 0);
    const paymentLabel = repair.final_payment_method
        ? (PAYMENT_METHOD_LABELS[repair.final_payment_method] ?? repair.final_payment_method.replace(/_/g, ' '))
        : null;

    return (
        <AdminShell>
            <Head title={`Receipt — Repair #${repair.id}`} />

            <div className="print:hidden mb-6 max-w-[340px] mx-auto flex items-center justify-between">
                <a href={`/admin/repairs/${repair.id}`} className="text-sm font-semibold text-admin-blue inline-flex items-center gap-1">
                    <Icon name="arrow-left" />
                    Back to repair
                </a>
                <AdminButton onClick={() => window.print()}>
                    <Icon name="printer" className="mr-1.5" />
                    Print receipt
                </AdminButton>
            </div>

            <div className="print-area max-w-[340px] print:max-w-[480px] mx-auto shadow-admin-card">
                <div className="receipt-torn-edge receipt-torn-edge-top" aria-hidden="true" />

                <div className="bg-admin-paper px-5 pt-5 pb-6 font-mono text-admin-text">
                    <div className="text-center mb-3">
                        <div className="text-base font-bold uppercase tracking-wide">{shopName}</div>
                        <div className="text-xs text-admin-text2">{shop?.name ?? 'Unknown shop'}</div>
                    </div>

                    <div className="border-t border-dashed border-admin-border-strong my-3" />

                    <div className="text-xs text-admin-text2 flex justify-between">
                        <span>Date</span>
                        <span>{new Date(repair.created_at).toLocaleString()}</span>
                    </div>
                    <div className="text-xs text-admin-text2 flex justify-between mt-1">
                        <span>Repair #</span>
                        <span className="font-semibold text-admin-text">{repair.id}</span>
                    </div>
                    <div className="text-xs text-admin-text2 flex justify-between mt-1">
                        <span>Device</span>
                        <span className="font-semibold text-admin-text">{repair.device_make} {repair.device_model}</span>
                    </div>
                    {repair.device_imei_serial && (
                        <div className="text-xs text-admin-text2 flex justify-between mt-1">
                            <span>IMEI / serial</span>
                            <span>{repair.device_imei_serial}</span>
                        </div>
                    )}
                    <div className="text-xs text-admin-text2 flex justify-between mt-1">
                        <span>Customer</span>
                        <span>{repair.customer_name ?? repair.walk_in_name ?? 'Walk-in'}</span>
                    </div>
                    {!repair.customer_name && repair.walk_in_phone && (
                        <div className="text-xs text-admin-text2 flex justify-between mt-1">
                            <span>Contact</span>
                            <span>{repair.walk_in_phone}</span>
                        </div>
                    )}

                    <div className="border-t border-dashed border-admin-border-strong my-3" />

                    {repair.fixed_faults.length > 0 ? (
                        <>
                            <div className="text-xs font-semibold text-admin-text2 mb-1.5">Faults fixed</div>
                            {repair.fixed_faults.map((fault) => (
                                <div key={fault.id} className="flex items-center justify-between gap-2 text-sm mb-1">
                                    <span className="truncate">{fault.label}</span>
                                    <FaultOriginBadge device_problem_tag_id={fault.device_problem_tag_id} common_component_id={fault.common_component_id} />
                                </div>
                            ))}
                            <div className="border-t border-dashed border-admin-border-strong my-3" />
                        </>
                    ) : null}

                    {repair.labour_charge_minor !== null && (
                        <div className="text-sm flex justify-between mt-1">
                            <span className="text-admin-text2">Labour charge</span>
                            <span>{formatNaira(repair.labour_charge_minor)}</span>
                        </div>
                    )}
                    {repair.diagnosis_fee && (
                        <div className="text-sm flex justify-between mt-1">
                            <span className="text-admin-text2">Diagnosis fee{repair.diagnosis_fee.paid ? '' : ' (unpaid)'}</span>
                            <span>{formatNaira(repair.diagnosis_fee.amount_minor)}</span>
                        </div>
                    )}
                    <div className="text-sm flex justify-between mt-1">
                        <span className="text-admin-text2">Repair cost</span>
                        <span>{repair.repair_cost_minor !== null ? formatNaira(repair.repair_cost_minor) : '—'}</span>
                    </div>
                    {repair.down_payment_paid_minor > 0 && (
                        <div className="text-sm flex justify-between mt-1">
                            <span className="text-admin-text2">Down payment paid</span>
                            <span>{formatNaira(repair.down_payment_paid_minor)}</span>
                        </div>
                    )}
                    {repair.final_payment_paid_minor > 0 && (
                        <div className="text-sm flex justify-between mt-1">
                            <span className="text-admin-text2">Final payment paid</span>
                            <span>{formatNaira(repair.final_payment_paid_minor)}</span>
                        </div>
                    )}

                    <div className="flex justify-between text-base font-bold mt-2 pt-2 border-t border-admin-border-strong">
                        <span>TOTAL PAID</span>
                        <span>{formatNaira(totalPaidMinor)}</span>
                    </div>

                    <div className="flex justify-center my-4">
                        <div className={`border-2 text-xs font-bold uppercase tracking-widest px-4 py-1 -rotate-6 rounded-admin-badge ${
                            repair.financial_status === 'fully_paid' ? 'border-admin-green text-admin-green' : 'border-admin-text3 text-admin-text2'
                        }`}>
                            {repair.financial_status === 'fully_paid'
                                ? `Paid${paymentLabel ? ` — ${paymentLabel}` : ''}`
                                : repair.financial_status.replace(/_/g, ' ')}
                        </div>
                    </div>

                    <div className="border-t border-dashed border-admin-border-strong my-3" />

                    <p className="text-center text-xs text-admin-text2">Thank you for choosing {shopName}.</p>
                    <p className="text-center text-[10px] text-admin-text3 mt-1">Keep this receipt as proof of service and payment.</p>

                    <div className="receipt-barcode mt-4" aria-hidden="true" />
                    <div className="text-center text-[10px] tracking-[0.3em] text-admin-text3 mt-1">REPAIR-{repair.id}</div>
                </div>

                <div className="receipt-torn-edge" aria-hidden="true" />
            </div>
        </AdminShell>
    );
}
