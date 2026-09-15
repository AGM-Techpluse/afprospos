import { FormEvent, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { useConfirm } from '../../../hooks/useConfirm';
import AdminShell from '../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../Components/Admin/AdminPageHead';
import AdminButton from '../../../Components/Admin/AdminButton';
import AdminBadge, { AdminBadgeStatus } from '../../../Components/Admin/AdminBadge';
import { formatNaira } from '../../../lib/money';

type PaymentDetail = {
    id: number;
    payable_type: string;
    payable_id: number;
    method: string;
    amount_minor: number;
    status: string;
    provider_reference: string | null;
    confirmed_by_staff_id: number | null;
    dispute_opened_at: string | null;
    dispute_proof_reference: string | null;
    dispute_resolved_by_staff_id: number | null;
    created_at: string;
};

interface PaymentShowProps {
    payment: PaymentDetail;
}

const STATUS_BADGE: Record<string, AdminBadgeStatus> = {
    pending: 'neutral',
    payment_pending_confirmation: 'warning',
    confirmed: 'success',
    disputed: 'danger',
    refunded: 'info',
    exception: 'danger',
};

const STATUS_LABEL: Record<string, string> = {
    pending: 'Pending',
    payment_pending_confirmation: 'Awaiting confirmation',
    confirmed: 'Confirmed',
    disputed: 'Disputed',
    refunded: 'Refunded',
    exception: 'Exception',
};

function DetailRow({ label, value }: { label: string; value: React.ReactNode }) {
    return (
        <div className="flex items-center justify-between py-2.5 border-b border-admin-border last:border-b-0">
            <span className="text-sm text-admin-text2">{label}</span>
            <span className="text-sm font-medium text-admin-text">{value}</span>
        </div>
    );
}

export default function PaymentShow({ payment }: PaymentShowProps) {
    const confirm = useConfirm();
    const [resolution, setResolution] = useState('confirmed');
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
        post(`/admin/payments/${payment.id}/dispute/resolve`, { resolution });
    }

    async function refundPayment() {
        const confirmed = await confirm({
            kind: 'danger',
            title: 'Refund this payment?',
            body: 'This marks the full amount as refunded. It cannot be reversed from here.',
            confirmLabel: 'Refund payment',
            cancelLabel: 'Cancel',
        });

        if (confirmed) {
            post(`/admin/payments/${payment.id}/refund`);
        }
    }

    return (
        <AdminShell>
            <Head title={`Payment #${payment.id}`} />

            <AdminPageHead
                title={`Payment #${payment.id}`}
                description={`${payment.payable_type.replace('_', ' ')} #${payment.payable_id}`}
                actions={<AdminBadge status={STATUS_BADGE[payment.status] ?? 'neutral'}>{STATUS_LABEL[payment.status] ?? payment.status}</AdminBadge>}
            />

            <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px] items-start max-w-4xl">
                <div className="bg-admin-surface border border-admin-border rounded-admin-card p-6">
                    <DetailRow label="Method" value={payment.method.replace('_', ' ')} />
                    <DetailRow label="Amount" value={formatNaira(payment.amount_minor)} />
                    <DetailRow label="Provider reference" value={payment.provider_reference ?? '—'} />
                    <DetailRow label="Confirmed by" value={payment.confirmed_by_staff_id ? `Staff #${payment.confirmed_by_staff_id}` : '—'} />
                    {payment.dispute_opened_at && (
                        <>
                            <DetailRow label="Dispute opened" value={new Date(payment.dispute_opened_at).toLocaleString()} />
                            <DetailRow label="Dispute proof" value={payment.dispute_proof_reference ?? '—'} />
                            <DetailRow
                                label="Dispute resolved by"
                                value={payment.dispute_resolved_by_staff_id ? `Staff #${payment.dispute_resolved_by_staff_id}` : '—'}
                            />
                        </>
                    )}
                    <DetailRow label="Created" value={new Date(payment.created_at).toLocaleString()} />
                </div>

                <div className="bg-admin-surface border border-admin-border rounded-admin-card p-6 flex flex-col gap-2">
                    <h2 className="text-sm font-semibold text-admin-text mb-1">Actions</h2>

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

                    {payment.status === 'confirmed' && (
                        <AdminButton variant="neutral" onClick={refundPayment} disabled={processing}>
                            Refund payment
                        </AdminButton>
                    )}

                    {payment.status === 'disputed' && (
                        <form onSubmit={resolveDispute} className="border-t border-admin-border pt-3 mt-1">
                            <label className="text-xs font-semibold text-admin-text mb-1.5 block">Resolve dispute as</label>
                            <select
                                value={resolution}
                                onChange={(e) => setResolution(e.target.value)}
                                className="w-full border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs mb-2"
                            >
                                <option value="confirmed">Confirmed</option>
                                <option value="refunded">Refunded</option>
                                <option value="exception">Exception</option>
                            </select>
                            <AdminButton type="submit" isLoading={processing}>
                                Resolve dispute
                            </AdminButton>
                        </form>
                    )}

                    {(payment.status === 'refunded' || payment.status === 'exception') && (
                        <p className="text-xs text-admin-text3">This payment is in a terminal state — no further actions are available.</p>
                    )}
                </div>
            </div>
        </AdminShell>
    );
}
