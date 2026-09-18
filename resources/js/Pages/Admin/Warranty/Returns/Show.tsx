import { FormEvent, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminShell from '../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../Components/Admin/AdminPageHead';
import AdminBadge, { AdminBadgeStatus } from '../../../../Components/Admin/AdminBadge';
import AdminButton from '../../../../Components/Admin/AdminButton';
import AdminInput from '../../../../Components/Admin/Forms/AdminInput';
import AdminTextarea from '../../../../Components/Admin/Forms/AdminTextarea';
import { formatNaira } from '../../../../lib/money';

interface Sale {
    id: number;
    invoice_number: string | null;
    total_minor: number;
    payment_transaction_id: number | null;
    created_at: string;
}

interface ReturnRequestDetail {
    id: number;
    sale: Sale | null;
    customer: { name: string; phone: string } | null;
    resolution_state: string;
    return_window_expires_at: string;
    denial_reason: string | null;
    override_approved_by_staff_id: number | null;
    refund_transaction_id: number | null;
    created_at: string;
}

interface ReturnRequestShowProps {
    returnRequest: ReturnRequestDetail;
}

const STATE_BADGE: Record<string, AdminBadgeStatus> = {
    requested: 'info',
    under_assessment: 'warning',
    approved: 'success',
    denied: 'danger',
    resolved: 'neutral',
};

const STATE_LABEL: Record<string, string> = {
    requested: 'Requested',
    under_assessment: 'Under assessment',
    approved: 'Approved',
    denied: 'Denied',
    resolved: 'Resolved',
};

function DetailRow({ label, value }: { label: string; value: React.ReactNode }) {
    return (
        <div className="flex items-center justify-between py-2.5 border-b border-admin-border last:border-b-0">
            <span className="text-sm text-admin-text2">{label}</span>
            <span className="text-sm font-medium text-admin-text">{value}</span>
        </div>
    );
}

export default function ReturnRequestShow({ returnRequest }: ReturnRequestShowProps) {
    const [processing, setProcessing] = useState(false);
    const [reason, setReason] = useState('');
    const [paymentTransactionId, setPaymentTransactionId] = useState(
        returnRequest.sale?.payment_transaction_id ? String(returnRequest.sale.payment_transaction_id) : '',
    );

    function post(url: string, data: Record<string, string> = {}) {
        setProcessing(true);
        router.post(url, data, { onFinish: () => setProcessing(false) });
    }

    function assess(e: FormEvent) {
        e.preventDefault();
        post(`/admin/warranty/returns/${returnRequest.id}/assess`);
    }

    function approve(e: FormEvent) {
        e.preventDefault();
        post(`/admin/warranty/returns/${returnRequest.id}/approve`);
    }

    function deny(e: FormEvent) {
        e.preventDefault();
        post(`/admin/warranty/returns/${returnRequest.id}/deny`, { reason });
    }

    function overrideApprove() {
        post(`/admin/warranty/returns/${returnRequest.id}/override-approve`);
    }

    function processRefund(e: FormEvent) {
        e.preventDefault();
        post(`/admin/warranty/returns/${returnRequest.id}/process-refund`, { payment_transaction_id: paymentTransactionId });
    }

    const isExpired = new Date(returnRequest.return_window_expires_at) < new Date();
    const canAssess = returnRequest.resolution_state === 'requested';
    const canDecide = returnRequest.resolution_state === 'under_assessment';
    const canOverride = returnRequest.resolution_state === 'denied';
    const canRefund = returnRequest.resolution_state === 'approved';

    return (
        <AdminShell>
            <Head title={`Return #${returnRequest.id}`} />

            <AdminPageHead
                title={`Return #${returnRequest.id}`}
                description={returnRequest.customer?.name ?? 'Customer'}
                backHref="/admin/warranty/returns"
                backLabel="Back to returns"
                actions={
                    <AdminBadge status={STATE_BADGE[returnRequest.resolution_state] ?? 'neutral'}>
                        {STATE_LABEL[returnRequest.resolution_state] ?? returnRequest.resolution_state}
                    </AdminBadge>
                }
            />

            <div className="flex flex-col gap-6 max-w-2xl">
                <div className="bg-admin-surface border border-admin-border rounded-admin-card p-6">
                    {returnRequest.sale && (
                        <>
                            <DetailRow label="Sale" value={returnRequest.sale.invoice_number ?? `#${returnRequest.sale.id}`} />
                            <DetailRow label="Sale total" value={formatNaira(returnRequest.sale.total_minor)} />
                            <DetailRow label="Sale payment transaction" value={returnRequest.sale.payment_transaction_id ?? '—'} />
                        </>
                    )}
                    <DetailRow label="Customer phone" value={returnRequest.customer?.phone ?? '—'} />
                    <DetailRow
                        label="Return window"
                        value={
                            <span className={isExpired ? 'text-admin-red' : undefined}>
                                {new Date(returnRequest.return_window_expires_at).toLocaleDateString()} {isExpired ? '(expired)' : ''}
                            </span>
                        }
                    />
                    {returnRequest.denial_reason && <DetailRow label="Denial reason" value={returnRequest.denial_reason} />}
                    {returnRequest.refund_transaction_id && <DetailRow label="Refund transaction" value={`#${returnRequest.refund_transaction_id}`} />}
                    <DetailRow label="Requested" value={new Date(returnRequest.created_at).toLocaleString()} />
                </div>

                {canAssess && (
                    <div className="bg-admin-surface border border-admin-border rounded-admin-card p-6">
                        <div className="text-sm font-semibold text-admin-text mb-3">Start assessment</div>
                        <form onSubmit={assess}>
                            <AdminButton type="submit" isLoading={processing}>Move to under assessment</AdminButton>
                        </form>
                    </div>
                )}

                {canDecide && (
                    <div className="bg-admin-surface border border-admin-border rounded-admin-card p-6">
                        <div className="text-sm font-semibold text-admin-text mb-3">Decide this return</div>
                        {isExpired && (
                            <p className="text-xs text-admin-red mb-3">
                                This request is past its return window — approving requires the override action below instead.
                            </p>
                        )}
                        {!isExpired && (
                            <form onSubmit={approve} className="mb-4">
                                <AdminButton type="submit" isLoading={processing}>Approve</AdminButton>
                            </form>
                        )}
                        <form onSubmit={deny}>
                            <AdminTextarea
                                label="Denial reason"
                                value={reason}
                                onChange={(e) => setReason(e.target.value)}
                                placeholder="Why is this return being denied?"
                                required
                            />
                            <AdminButton type="submit" variant="warning" isLoading={processing}>Deny</AdminButton>
                        </form>
                    </div>
                )}

                {canOverride && (
                    <div className="bg-admin-surface border border-admin-border rounded-admin-card p-6">
                        <div className="text-sm font-semibold text-admin-text mb-1">Administrative override</div>
                        <p className="text-xs text-admin-text2 mb-3">
                            WAR-BR-12: approve this denied return anyway (e.g. outside the return window, exceptional case).
                        </p>
                        <AdminButton type="button" variant="warning" isLoading={processing} onClick={overrideApprove}>
                            Approve by override
                        </AdminButton>
                    </div>
                )}

                {canRefund && (
                    <div className="bg-admin-surface border border-admin-border rounded-admin-card p-6">
                        <div className="text-sm font-semibold text-admin-text mb-3">Process refund</div>
                        <p className="text-xs text-admin-text2 mb-3">
                            Requires the separate approve-refund permission (same separation of duties as Warranty Claims).
                        </p>
                        <form onSubmit={processRefund}>
                            <AdminInput
                                label="Payment transaction ID"
                                type="number"
                                min={1}
                                value={paymentTransactionId}
                                onChange={(e) => setPaymentTransactionId(e.target.value)}
                                required
                            />
                            <AdminButton type="submit" isLoading={processing}>Process refund</AdminButton>
                        </form>
                    </div>
                )}
            </div>
        </AdminShell>
    );
}
