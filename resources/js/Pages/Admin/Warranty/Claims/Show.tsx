import { FormEvent, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminShell from '../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../Components/Admin/AdminPageHead';
import AdminBadge, { AdminBadgeStatus } from '../../../../Components/Admin/AdminBadge';
import AdminButton from '../../../../Components/Admin/AdminButton';
import AdminInput from '../../../../Components/Admin/Forms/AdminInput';
import AdminTextarea from '../../../../Components/Admin/Forms/AdminTextarea';
import EligibilityChecklist from '../../../../Features/Warranty/EligibilityChecklist';

interface WarrantyClaimDetail {
    id: number;
    warranty_policy: {
        id: number;
        name: string;
        coverage_duration_days: number;
        coverage_start_point: string;
        covered_scope: string[];
        exclusions: string[];
        coverage_extent: string;
    } | null;
    originating_sale: { id: number; created_at: string } | null;
    originating_repair_job: { id: number; device_make: string; device_model: string; created_at: string } | null;
    customer: { name: string; phone: string } | null;
    resolution_state: string;
    assessment_notes: string | null;
    selected_remedy: string | null;
    remedy_reference_id: number | null;
    created_at: string;
}

interface WarrantyClaimShowProps {
    claim: WarrantyClaimDetail;
}

const STATE_BADGE: Record<string, AdminBadgeStatus> = {
    submitted: 'info',
    under_assessment: 'warning',
    eligible: 'success',
    not_eligible: 'danger',
    remedy_selected: 'warning',
    resolved: 'neutral',
};

const STATE_LABEL: Record<string, string> = {
    submitted: 'Submitted',
    under_assessment: 'Under assessment',
    eligible: 'Eligible',
    not_eligible: 'Not eligible',
    remedy_selected: 'Remedy selected',
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

export default function WarrantyClaimShow({ claim }: WarrantyClaimShowProps) {
    const [processing, setProcessing] = useState(false);
    const [eligible, setEligible] = useState(true);
    const [notes, setNotes] = useState('');
    const [remedy, setRemedy] = useState('repair');
    const [remedyReferenceId, setRemedyReferenceId] = useState('');
    const [paymentTransactionId, setPaymentTransactionId] = useState('');

    function post(url: string, data: Record<string, string> = {}) {
        setProcessing(true);
        router.post(url, data, { onFinish: () => setProcessing(false) });
    }

    function assess(e: FormEvent) {
        e.preventDefault();
        post(`/admin/warranty/claims/${claim.id}/assess`, { eligible: eligible ? '1' : '0', notes });
    }

    function selectRemedy(e: FormEvent) {
        e.preventDefault();
        post(`/admin/warranty/claims/${claim.id}/select-remedy`, { remedy });
    }

    function resolve(e: FormEvent) {
        e.preventDefault();
        post(`/admin/warranty/claims/${claim.id}/resolve`, { remedy_reference_id: remedyReferenceId });
    }

    function approveRefund(e: FormEvent) {
        e.preventDefault();
        post(`/admin/warranty/claims/${claim.id}/approve-refund`, { payment_transaction_id: paymentTransactionId });
    }

    const canAssess = claim.resolution_state === 'submitted' || claim.resolution_state === 'under_assessment';
    const canSelectRemedy = claim.resolution_state === 'eligible';
    const canExecuteRemedy = claim.resolution_state === 'remedy_selected';

    return (
        <AdminShell>
            <Head title={`Warranty claim #${claim.id}`} />

            <AdminPageHead
                title={`Warranty claim #${claim.id}`}
                description={claim.customer?.name ?? 'Customer'}
                backHref="/admin/warranty/claims"
                backLabel="Back to claims"
                actions={
                    <AdminBadge status={STATE_BADGE[claim.resolution_state] ?? 'neutral'}>
                        {STATE_LABEL[claim.resolution_state] ?? claim.resolution_state}
                    </AdminBadge>
                }
            />

            <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px] items-start max-w-4xl">
                <div className="flex flex-col gap-6">
                    <div className="bg-admin-surface border border-admin-border rounded-admin-card p-6">
                        <DetailRow label="Policy" value={claim.warranty_policy?.name ?? '—'} />
                        {claim.originating_sale && <DetailRow label="Originating sale" value={`Sale #${claim.originating_sale.id}`} />}
                        {claim.originating_repair_job && (
                            <DetailRow
                                label="Originating repair"
                                value={`#${claim.originating_repair_job.id} — ${claim.originating_repair_job.device_make} ${claim.originating_repair_job.device_model}`}
                            />
                        )}
                        <DetailRow label="Customer phone" value={claim.customer?.phone ?? '—'} />
                        {claim.selected_remedy && <DetailRow label="Selected remedy" value={claim.selected_remedy} />}
                        {claim.remedy_reference_id && <DetailRow label="Remedy reference" value={`#${claim.remedy_reference_id}`} />}
                        {claim.assessment_notes && (
                            <div className="pt-2.5">
                                <div className="text-sm text-admin-text2 mb-1">Assessment notes</div>
                                <p className="text-sm text-admin-text whitespace-pre-wrap">{claim.assessment_notes}</p>
                            </div>
                        )}
                        <DetailRow label="Submitted" value={new Date(claim.created_at).toLocaleString()} />
                    </div>

                    {canAssess && (
                        <div className="bg-admin-surface border border-admin-border rounded-admin-card p-6">
                            <div className="text-sm font-semibold text-admin-text mb-3">Assess this claim</div>
                            <form onSubmit={assess}>
                                <div className="flex items-center gap-4 mb-3">
                                    <label className="flex items-center gap-2 text-sm text-admin-text">
                                        <input type="radio" checked={eligible} onChange={() => setEligible(true)} />
                                        Eligible
                                    </label>
                                    <label className="flex items-center gap-2 text-sm text-admin-text">
                                        <input type="radio" checked={!eligible} onChange={() => setEligible(false)} />
                                        Not eligible
                                    </label>
                                </div>
                                <AdminTextarea
                                    label="Notes"
                                    value={notes}
                                    onChange={(e) => setNotes(e.target.value)}
                                    placeholder="What did you find on assessment?"
                                />
                                <AdminButton type="submit" isLoading={processing}>Record assessment</AdminButton>
                            </form>
                        </div>
                    )}

                    {canSelectRemedy && (
                        <div className="bg-admin-surface border border-admin-border rounded-admin-card p-6">
                            <div className="text-sm font-semibold text-admin-text mb-3">Select a remedy</div>
                            <form onSubmit={selectRemedy}>
                                <div className="flex flex-col gap-2 mb-3">
                                    {['repair', 'replace', 'refund'].map((option) => (
                                        <label key={option} className="flex items-center gap-2 text-sm text-admin-text capitalize">
                                            <input type="radio" checked={remedy === option} onChange={() => setRemedy(option)} />
                                            {option}
                                        </label>
                                    ))}
                                </div>
                                <AdminButton type="submit" isLoading={processing}>Request remedy</AdminButton>
                            </form>
                        </div>
                    )}

                    {canExecuteRemedy && claim.selected_remedy !== 'refund' && (
                        <div className="bg-admin-surface border border-admin-border rounded-admin-card p-6">
                            <div className="text-sm font-semibold text-admin-text mb-3">
                                Resolve — {claim.selected_remedy === 'repair' ? 'link the repair job' : 'link the replacement unit'}
                            </div>
                            <form onSubmit={resolve}>
                                <AdminInput
                                    label={claim.selected_remedy === 'repair' ? 'Repair job ID' : 'Replacement SKU ID'}
                                    type="number"
                                    min={1}
                                    value={remedyReferenceId}
                                    onChange={(e) => setRemedyReferenceId(e.target.value)}
                                    required
                                />
                                <AdminButton type="submit" isLoading={processing}>Mark resolved</AdminButton>
                            </form>
                        </div>
                    )}

                    {canExecuteRemedy && claim.selected_remedy === 'refund' && (
                        <div className="bg-admin-surface border border-admin-border rounded-admin-card p-6">
                            <div className="text-sm font-semibold text-admin-text mb-3">Approve refund</div>
                            <p className="text-xs text-admin-text2 mb-3">
                                Requires the separate approve-refund permission (WAR-BR-06's separation of duties).
                            </p>
                            <form onSubmit={approveRefund}>
                                <AdminInput
                                    label="Payment transaction ID"
                                    type="number"
                                    min={1}
                                    value={paymentTransactionId}
                                    onChange={(e) => setPaymentTransactionId(e.target.value)}
                                    required
                                />
                                <AdminButton type="submit" isLoading={processing}>Approve refund</AdminButton>
                            </form>
                        </div>
                    )}
                </div>

                {claim.warranty_policy && (
                    <div className="lg:sticky lg:top-6">
                        <EligibilityChecklist
                            coveredScope={claim.warranty_policy.covered_scope}
                            exclusions={claim.warranty_policy.exclusions}
                        />
                    </div>
                )}
            </div>
        </AdminShell>
    );
}
