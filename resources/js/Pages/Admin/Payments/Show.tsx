import { FormEvent, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { useConfirm } from '../../../hooks/useConfirm';
import AdminShell from '../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../Components/Admin/AdminPageHead';
import AdminDetailRow from '../../../Components/Admin/AdminDetailRow';
import AdminSectionCard from '../../../Components/Admin/AdminSectionCard';
import AdminButton from '../../../Components/Admin/AdminButton';
import AdminBadge, { AdminBadgeStatus } from '../../../Components/Admin/AdminBadge';
import AdminSelect from '../../../Components/Admin/Forms/AdminSelect';
import { formatNaira } from '../../../lib/money';

type PaymentRefund = {
    amount_minor: number;
    refunded_by_staff_name: string | null;
    source: 'manual_refund' | 'dispute_resolution';
    created_at: string | null;
};

type SaleLineItem = {
    product_name: string | null;
    variant_label: string | null;
    quantity: number;
    unit_price_minor: number;
    line_total_minor: number;
};

type SaleDetail = {
    subtotal_minor: number;
    discount_minor: number;
    total_minor: number;
    items: SaleLineItem[];
};

type PaymentDetail = {
    id: number;
    payable_type: string;
    payable_id: number;
    purpose: string;
    method: string;
    amount_minor: number;
    status: string;
    provider_reference: string | null;
    confirmed_by_staff_id: number | null;
    confirmed_by_staff_name: string | null;
    dispute_opened_at: string | null;
    dispute_proof_reference: string | null;
    dispute_resolved_by_staff_id: number | null;
    dispute_resolved_by_staff_name: string | null;
    created_at: string;
    source: { label: string; link: string };
    customer_name: string | null;
    sale: SaleDetail | null;
    refunded_amount_minor: number;
    remaining_refundable_minor: number;
    refunds: PaymentRefund[];
};

const PURPOSE_LABEL: Record<string, string> = {
    sale_payment: 'Sale payment',
    repair_down_payment: 'Repair down payment',
    repair_final_payment: 'Repair final payment',
    repair_diagnosis_fee: 'Diagnosis fee',
};

interface PaymentShowProps {
    payment: PaymentDetail;
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

export default function PaymentShow({ payment }: PaymentShowProps) {
    const confirm = useConfirm();
    const [resolution, setResolution] = useState('confirmed');
    const remainingNaira = (payment.remaining_refundable_minor / 100).toFixed(2);
    const [disputeRefundNaira, setDisputeRefundNaira] = useState(remainingNaira);
    const [standaloneRefundNaira, setStandaloneRefundNaira] = useState(remainingNaira);
    const [processing, setProcessing] = useState(false);

    function post(url: string, data: Record<string, string> = {}) {
        setProcessing(true);
        router.post(url, data, { onFinish: () => setProcessing(false) });
    }

    async function confirmPayment() {
        post(`/admin/payments/${payment.id}/confirm`);
    }

    async function rejectPayment() {
        const confirmed = await confirm({
            kind: 'danger',
            title: 'Reject this payment?',
            body: 'This moves the payment to an exception state. It cannot be reversed from here.',
            confirmLabel: 'Reject payment',
            cancelLabel: 'Keep pending',
        });

        if (confirmed) {
            post(`/admin/payments/${payment.id}/reject`);
        }
    }

    async function openDispute() {
        post(`/admin/payments/${payment.id}/dispute`);
    }

    function resolveDispute(e: FormEvent) {
        e.preventDefault();
        const data: Record<string, string> = { resolution };
        if (resolution === 'refunded') {
            data.amount_minor = Math.round(parseFloat(disputeRefundNaira || '0') * 100).toString();
        }
        post(`/admin/payments/${payment.id}/dispute/resolve`, data);
    }

    function refundPayment(e: FormEvent) {
        e.preventDefault();
        post(`/admin/payments/${payment.id}/refund`, {
            amount_minor: Math.round(parseFloat(standaloneRefundNaira || '0') * 100).toString(),
        });
    }

    return (
        <AdminShell>
            <Head title={`Payment #${payment.id}`} />

            <AdminPageHead
                title={`Payment #${payment.id}`}
                description={PURPOSE_LABEL[payment.purpose] ?? payment.purpose.replace(/_/g, ' ')}
                actions={<AdminBadge status={STATUS_BADGE[payment.status] ?? 'neutral'}>{STATUS_LABEL[payment.status] ?? payment.status}</AdminBadge>}
            />

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_360px] items-start max-w-4xl">
                <AdminSectionCard title="Payment details">
                    <div className="p-6">
                        {/* Customer & Source Reference enclosed in a dedicated bordered box */}
                        <div className="border border-admin-border rounded-admin-card p-4 mb-5 bg-admin-bg/30">
                            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                <div>
                                    <div className="text-[11px] font-bold uppercase tracking-wider text-admin-text3 mb-0.5">Customer</div>
                                    <p className="text-sm font-semibold text-admin-text">
                                        {payment.customer_name ?? 'Walk-in'}
                                    </p>
                                </div>
                                <div className="sm:text-right">
                                    <div className="text-[11px] font-bold uppercase tracking-wider text-admin-text3 mb-0.5">Reference source</div>
                                    <Link href={payment.source.link} className="text-xs font-semibold text-admin-blue hover:underline inline-flex items-center gap-1">
                                        {payment.source.label} →
                                    </Link>
                                </div>
                            </div>
                        </div>

                        {/* Category 1: Transaction details */}
                        <div className="pt-1 pb-1">
                            <span className="text-[11px] font-bold uppercase tracking-wider text-admin-text3">Transaction Info</span>
                        </div>

                        <AdminDetailRow label="Purpose" value={PURPOSE_LABEL[payment.purpose] ?? payment.purpose.replace(/_/g, ' ')} />
                        <AdminDetailRow label="Method" value={<span className="capitalize">{payment.method.replace('_', ' ')}</span>} />
                        <AdminDetailRow label="Amount" value={<span className="font-bold text-admin-text">{formatNaira(payment.amount_minor)}</span>} />
                        {payment.sale && (
                            <>
                                <AdminDetailRow label="Subtotal" value={formatNaira(payment.sale.subtotal_minor)} />
                                <AdminDetailRow
                                    label="Discount applied"
                                    value={payment.sale.discount_minor > 0 ? `− ${formatNaira(payment.sale.discount_minor)}` : 'None'}
                                />
                                <AdminDetailRow
                                    label="Total"
                                    value={<span className="font-bold text-admin-text">{formatNaira(payment.sale.total_minor)}</span>}
                                />
                            </>
                        )}
                        <AdminDetailRow label="Provider reference" value={payment.provider_reference ?? '—'} />
                        <AdminDetailRow label="Confirmed by" value={payment.confirmed_by_staff_id ? (payment.confirmed_by_staff_name ?? 'Staff member') : '—'} />
                        <AdminDetailRow label="Created" value={new Date(payment.created_at).toLocaleString()} />

                        {/* Category 2: Refunds & Disputes (if any) */}
                        {(payment.refunded_amount_minor > 0 || payment.dispute_opened_at || payment.refunds.length > 1) && (
                            <div className="pt-5 border-t border-admin-border mt-3">
                                <div className="text-[11px] font-bold uppercase tracking-wider text-admin-text3 mb-1">
                                    Refunds & Disputes
                                </div>
                                {payment.refunded_amount_minor > 0 && (
                                    <>
                                        <AdminDetailRow label="Refunded so far" value={formatNaira(payment.refunded_amount_minor)} />
                                        <AdminDetailRow label="Remaining refundable" value={formatNaira(payment.remaining_refundable_minor)} />
                                    </>
                                )}
                                {payment.dispute_opened_at && (
                                    <>
                                        <AdminDetailRow label="Dispute opened" value={new Date(payment.dispute_opened_at).toLocaleString()} />
                                        <AdminDetailRow label="Dispute proof" value={payment.dispute_proof_reference ?? '—'} />
                                        <AdminDetailRow
                                            label="Dispute resolved by"
                                            value={payment.dispute_resolved_by_staff_id ? (payment.dispute_resolved_by_staff_name ?? 'Staff member') : '—'}
                                        />
                                    </>
                                )}
                                {payment.refunds.length > 1 && (
                                    <div className="py-2.5 border-b border-admin-border last:border-b-0">
                                        <span className="text-xs text-admin-text2 block mb-1.5 font-medium">Refund log</span>
                                        <ul className="space-y-1">
                                            {payment.refunds.map((refund, index) => (
                                                <li key={index} className="flex items-center justify-between text-xs text-admin-text2 py-1 px-2.5 bg-admin-bg/40 rounded-admin-button">
                                                    <span>
                                                        {refund.refunded_by_staff_name ?? 'Staff member'}
                                                        {refund.created_at && ` · ${new Date(refund.created_at).toLocaleDateString()}`}
                                                    </span>
                                                    <span className="font-semibold text-admin-text">{formatNaira(refund.amount_minor)}</span>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                )}
                            </div>
                        )}

                        {/* Category 3: Items purchased & breakdown (at the bottom) */}
                        {payment.sale && (
                            <div className="pt-5 border-t border-admin-border mt-3">
                                <div className="text-[11px] font-bold uppercase tracking-wider text-admin-text3 mb-3">
                                    Items purchased
                                </div>
                                <div className="border border-admin-border rounded-admin-card p-3.5 bg-admin-bg/30 mb-4">
                                    <ul className="flex flex-col divide-y divide-admin-border text-sm text-admin-text">
                                        {payment.sale.items.map((item, index) => (
                                            <li key={index} className="flex justify-between items-center py-2 first:pt-0 last:pb-0 gap-3">
                                                <div className="min-w-0">
                                                    <div className="text-xs font-semibold text-admin-text truncate">
                                                        {item.product_name ?? 'Unknown product'}
                                                        {item.variant_label && <span className="text-admin-text3 font-normal"> · {item.variant_label}</span>}
                                                    </div>
                                                    <div className="text-[11px] text-admin-text3 mt-0.5">
                                                        {item.quantity} × {formatNaira(item.unit_price_minor)}
                                                    </div>
                                                </div>
                                                <span className="shrink-0 font-semibold text-xs text-admin-text font-mono">
                                                    {formatNaira(item.line_total_minor)}
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            </div>
                        )}
                    </div>
                </AdminSectionCard>

                <AdminSectionCard title="Actions">
                    <div className="p-6 flex flex-col gap-2">

                    {(payment.status === 'pending' || payment.status === 'payment_pending_confirmation') && (
                        <>
                            <AdminButton onClick={confirmPayment} isLoading={processing}>
                                Confirm payment
                            </AdminButton>
                            <AdminButton variant="neutral" onClick={rejectPayment} disabled={processing}>
                                Reject payment
                            </AdminButton>
                        </>
                    )}

                    {(payment.status === 'payment_pending_confirmation' || payment.status === 'confirmed') && (
                        <AdminButton variant="neutral" onClick={openDispute} disabled={processing}>
                            Open dispute
                        </AdminButton>
                    )}

                    {(payment.status === 'confirmed' || payment.status === 'partially_refunded') && (
                        <form onSubmit={refundPayment} className="border-t border-admin-border pt-3 mt-1">
                            <label className="text-xs font-semibold text-admin-text mb-1.5 block">
                                Refund amount (₦, up to {formatNaira(payment.remaining_refundable_minor)})
                            </label>
                            <input
                                type="number"
                                step="0.01"
                                min="0.01"
                                max={payment.remaining_refundable_minor / 100}
                                value={standaloneRefundNaira}
                                onChange={(e) => setStandaloneRefundNaira(e.target.value)}
                                className="w-full border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs mb-2"
                            />
                            <AdminButton type="submit" variant="neutral" isLoading={processing}>
                                Refund payment
                            </AdminButton>
                        </form>
                    )}

                    {payment.status === 'disputed' && (
                        <form onSubmit={resolveDispute} className="border-t border-admin-border pt-3 mt-1">
                            <AdminSelect label="Resolve dispute as" value={resolution} onChange={(e) => setResolution(e.target.value)}>
                                <option value="confirmed">Confirmed</option>
                                <option value="refunded">Refunded</option>
                                <option value="exception">Exception</option>
                            </AdminSelect>
                            {resolution === 'refunded' && (
                                <>
                                    <label className="text-xs font-semibold text-admin-text mb-1.5 block">
                                        Refund amount (₦, up to {formatNaira(payment.remaining_refundable_minor)})
                                    </label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        max={payment.remaining_refundable_minor / 100}
                                        value={disputeRefundNaira}
                                        onChange={(e) => setDisputeRefundNaira(e.target.value)}
                                        className="w-full border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs mb-2"
                                    />
                                </>
                            )}
                            <AdminButton type="submit" isLoading={processing}>
                                Resolve dispute
                            </AdminButton>
                        </form>
                    )}

                    {(payment.status === 'refunded' || payment.status === 'exception') && (
                        <p className="text-xs text-admin-text3">This payment is in a terminal state — no further actions are available.</p>
                    )}
                </div>
                </AdminSectionCard>
            </div>
        </AdminShell>
    );
}
