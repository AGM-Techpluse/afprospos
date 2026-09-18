import { FormEvent, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminShell from '../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../Components/Admin/AdminPageHead';
import AdminBadge, { AdminBadgeStatus } from '../../../../Components/Admin/AdminBadge';
import AdminButton from '../../../../Components/Admin/AdminButton';
import AdminInput from '../../../../Components/Admin/Forms/AdminInput';
import { formatNaira } from '../../../../lib/money';

interface TradeInDetail {
    id: number;
    customer_id: number;
    customer: { name: string; phone: string } | null;
    related_checkout_id: number | null;
    device_description: { make?: string; model?: string; imei?: string | null; condition?: string };
    assessed_value_minor: number | null;
    resolution_state: string;
    assessed_by_staff_id: number | null;
    approved_by_staff_id: number | null;
    created_at: string;
}

interface TradeInShowProps {
    tradeIn: TradeInDetail;
}

const STATE_BADGE: Record<string, AdminBadgeStatus> = {
    submitted: 'info',
    assessed: 'warning',
    approved: 'success',
    rejected: 'danger',
    applied: 'neutral',
};

const STATE_LABEL: Record<string, string> = {
    submitted: 'Submitted',
    assessed: 'Assessed',
    approved: 'Approved',
    rejected: 'Rejected',
    applied: 'Applied',
};

function DetailRow({ label, value }: { label: string; value: React.ReactNode }) {
    return (
        <div className="flex items-center justify-between py-2.5 border-b border-admin-border last:border-b-0">
            <span className="text-sm text-admin-text2">{label}</span>
            <span className="text-sm font-medium text-admin-text">{value}</span>
        </div>
    );
}

export default function TradeInShow({ tradeIn }: TradeInShowProps) {
    const [processing, setProcessing] = useState(false);
    const [assessedValue, setAssessedValue] = useState('');
    const [checkoutId, setCheckoutId] = useState('');

    function post(url: string, data: Record<string, string> = {}) {
        setProcessing(true);
        router.post(url, data, { onFinish: () => setProcessing(false) });
    }

    function assess(e: FormEvent) {
        e.preventDefault();
        post(`/admin/warranty/trade-ins/${tradeIn.id}/assess`, {
            assessed_value_minor: String(Math.round(parseFloat(assessedValue || '0') * 100)),
        });
    }

    function approve() {
        post(`/admin/warranty/trade-ins/${tradeIn.id}/approve`);
    }

    function reject() {
        post(`/admin/warranty/trade-ins/${tradeIn.id}/reject`);
    }

    function applyCredit(e: FormEvent) {
        e.preventDefault();
        post(`/admin/warranty/trade-ins/${tradeIn.id}/apply-credit`, { checkout_id: checkoutId });
    }

    const canAssess = tradeIn.resolution_state === 'submitted';
    const canDecide = tradeIn.resolution_state === 'assessed';
    const canApplyCredit = tradeIn.resolution_state === 'approved';

    return (
        <AdminShell>
            <Head title={`Trade-in #${tradeIn.id}`} />

            <AdminPageHead
                title={`Trade-in #${tradeIn.id}`}
                description={tradeIn.customer?.name ?? 'Customer'}
                backHref="/admin/warranty/trade-ins"
                backLabel="Back to trade-ins"
                actions={
                    <AdminBadge status={STATE_BADGE[tradeIn.resolution_state] ?? 'neutral'}>
                        {STATE_LABEL[tradeIn.resolution_state] ?? tradeIn.resolution_state}
                    </AdminBadge>
                }
            />

            <div className="flex flex-col gap-6 max-w-2xl">
                <div className="bg-admin-surface border border-admin-border rounded-admin-card p-6">
                    <DetailRow label="Device" value={`${tradeIn.device_description.make ?? ''} ${tradeIn.device_description.model ?? ''}`.trim() || '—'} />
                    {tradeIn.device_description.imei && <DetailRow label="IMEI" value={tradeIn.device_description.imei} />}
                    {tradeIn.device_description.condition && (
                        <div className="pt-2.5 pb-2.5 border-b border-admin-border">
                            <div className="text-sm text-admin-text2 mb-1">Condition notes</div>
                            <p className="text-sm text-admin-text whitespace-pre-wrap">{tradeIn.device_description.condition}</p>
                        </div>
                    )}
                    <DetailRow label="Customer phone" value={tradeIn.customer?.phone ?? '—'} />
                    <DetailRow label="Linked checkout" value={tradeIn.related_checkout_id ? `#${tradeIn.related_checkout_id}` : '—'} />
                    <DetailRow label="Assessed value" value={tradeIn.assessed_value_minor !== null ? formatNaira(tradeIn.assessed_value_minor) : '—'} />
                    <DetailRow label="Recorded" value={new Date(tradeIn.created_at).toLocaleString()} />
                </div>

                {tradeIn.resolution_state === 'applied' && (
                    <div className="bg-admin-surface border border-admin-border rounded-admin-card p-6 text-sm text-admin-text2">
                        This trade-in's credit has been applied to checkout #{tradeIn.related_checkout_id}. TRADE-BR-04: remember to record the accepted
                        device as inventory acquired by the business via the Inventory module.
                    </div>
                )}

                {canAssess && (
                    <div className="bg-admin-surface border border-admin-border rounded-admin-card p-6">
                        <div className="text-sm font-semibold text-admin-text mb-3">Assess this trade-in</div>
                        <form onSubmit={assess}>
                            <AdminInput
                                label="Assessed value (₦)"
                                type="number"
                                min={0}
                                step="0.01"
                                value={assessedValue}
                                onChange={(e) => setAssessedValue(e.target.value)}
                                required
                            />
                            <AdminButton type="submit" isLoading={processing}>Record assessment</AdminButton>
                        </form>
                    </div>
                )}

                {canDecide && (
                    <div className="bg-admin-surface border border-admin-border rounded-admin-card p-6">
                        <div className="text-sm font-semibold text-admin-text mb-1">Approve or reject</div>
                        <p className="text-xs text-admin-text2 mb-3">
                            TRADE-BR-05: the approver must be a different staff member than whoever assessed the value.
                        </p>
                        <div className="flex items-center gap-2">
                            <AdminButton type="button" isLoading={processing} onClick={approve}>Approve</AdminButton>
                            <AdminButton type="button" variant="warning" isLoading={processing} onClick={reject}>Reject</AdminButton>
                        </div>
                    </div>
                )}

                {canApplyCredit && !tradeIn.related_checkout_id && (
                    <div className="bg-admin-surface border border-admin-border rounded-admin-card p-6">
                        <div className="text-sm font-semibold text-admin-text mb-3">Apply credit to a checkout</div>
                        <p className="text-xs text-admin-text2 mb-3">
                            No checkout was linked when this trade-in was recorded — apply the credit to the customer's current checkout.
                        </p>
                        <form onSubmit={applyCredit}>
                            <AdminInput
                                label="Checkout ID"
                                type="number"
                                min={1}
                                value={checkoutId}
                                onChange={(e) => setCheckoutId(e.target.value)}
                                required
                            />
                            <AdminButton type="submit" isLoading={processing}>Apply credit</AdminButton>
                        </form>
                    </div>
                )}
            </div>
        </AdminShell>
    );
}
