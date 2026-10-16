import { FormEvent, useEffect, useMemo, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AdminShell from '../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../Components/Admin/AdminPageHead';
import AdminButton from '../../../Components/Admin/AdminButton';
import AdminBadge from '../../../Components/Admin/AdminBadge';
import FaultOriginBadge from '../../../Features/Repairs/FaultOriginBadge';
import { formatNaira } from '../../../lib/money';

type Technician = { id: number; name: string };
type FaultCatalogItem = { id: number; label: string };
type RevealedFault = { id: number; device_problem_tag_id: number | null; common_component_id: number | null; label: string; notes: string | null };
type DiagnosisCase = {
    id: number;
    shop_id: number;
    customer_name: string | null;
    customer_phone: string | null;
    customer_email: string | null;
    walk_in_name: string | null;
    walk_in_phone: string | null;
    walk_in_email: string | null;
    device_make: string;
    device_model: string;
    device_imei_serial: string | null;
    device_lock_present: boolean;
    device_lock_visible_to_you: boolean;
    device_lock_value: string | null;
    reported_issue: string | null;
    diagnosis_technician_staff_id: number | null;
    status: string;
    outcome: string | null;
    diagnosis_notes: string | null;
    diagnosis_fee_minor: number | null;
    diagnosis_fee_paid: boolean;
    diagnosis_fee_payment_method: string | null;
    revealed_faults: RevealedFault[];
    created_at: string;
};
type RelatedRepair = {
    id: number;
    status: string;
    display_label: string;
    opened_at: string;
    created_at: string;
    reopened_from_repair_job_id: number | null;
};

interface Props {
    diagnosisCase: DiagnosisCase;
    shop: { id: number; name: string } | null;
    technicians: Technician[];
    faultCatalog: FaultCatalogItem[];
    relatedRepairs: RelatedRepair[];
}

function Row({ label, value }: { label: string; value: React.ReactNode }) {
    return (
        <div className="flex items-center justify-between gap-4 py-2.5 border-b border-admin-border last:border-b-0">
            <span className="text-sm text-admin-text2">{label}</span>
            <span className="text-sm font-medium text-admin-text text-right">{value}</span>
        </div>
    );
}

export default function DiagnosisCaseShow({ diagnosisCase, shop, technicians, faultCatalog, relatedRepairs }: Props) {
    const [technicianId, setTechnicianId] = useState(diagnosisCase.diagnosis_technician_staff_id?.toString() ?? '');
    const [assignedTechnicianId, setAssignedTechnicianId] = useState(diagnosisCase.diagnosis_technician_staff_id?.toString() ?? '');
    const [selectedFaultId, setSelectedFaultId] = useState('');
    const [customFault, setCustomFault] = useState('');
    const [diagnosisSaved, setDiagnosisSaved] = useState(false);
    const [feeMethod, setFeeMethod] = useState('cash');
    const [collectingFee, setCollectingFee] = useState(false);
    const { data, setData, processing, errors } = useForm({
        outcome: diagnosisCase.outcome ?? 'repairable',
        diagnosis_notes: diagnosisCase.diagnosis_notes ?? '',
        diagnosis_fee_naira: diagnosisCase.diagnosis_fee_minor !== null ? String(diagnosisCase.diagnosis_fee_minor / 100) : '',
        revealed_faults: diagnosisCase.revealed_faults.map((fault) => ({
            device_problem_tag_id: fault.device_problem_tag_id,
            common_component_id: fault.common_component_id,
            label: fault.label,
            notes: fault.notes ?? '',
        })),
    });

    const revealedLabels = useMemo(() => new Set(data.revealed_faults.map((fault) => fault.label)), [data.revealed_faults]);
    const openRepair = relatedRepairs.find((repair) => !['completed', 'unrepairable', 'expired_cancelled', 'cancelled'].includes(repair.status));
    const hasCompletedRepair = relatedRepairs.some((repair) => repair.status === 'completed');
    const assignedTechnicianName = technicians.find((technician) => String(technician.id) === assignedTechnicianId)?.name ?? 'Unassigned';

    useEffect(() => {
        setAssignedTechnicianId(diagnosisCase.diagnosis_technician_staff_id?.toString() ?? '');
        setTechnicianId(diagnosisCase.diagnosis_technician_staff_id?.toString() ?? '');
    }, [diagnosisCase.diagnosis_technician_staff_id]);

    function assignTechnician(e: FormEvent) {
        e.preventDefault();
        if (!technicianId) return;
        router.post(
            `/admin/repairs/diagnosis-cases/${diagnosisCase.id}/assign-technician`,
            { diagnosis_technician_staff_id: technicianId },
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => setAssignedTechnicianId(technicianId),
            },
        );
    }

    function changeDiagnosisField<T extends keyof typeof data>(field: T, value: (typeof data)[T]) {
        setDiagnosisSaved(false);
        // `setData`'s generic can't be proven to correlate with this wrapper's own
        // generic `T` (a known TS limitation with generic setter wrappers over a
        // union of heterogeneous field types) — the signature above already
        // guarantees `value` matches `field`'s real type.
        setData(field, value as never);
    }

    function addFault() {
        const catalogFault = faultCatalog.find((fault) => String(fault.id) === selectedFaultId);
        if (!catalogFault || revealedLabels.has(catalogFault.label)) return;
        changeDiagnosisField('revealed_faults', [...data.revealed_faults, { device_problem_tag_id: catalogFault.id, common_component_id: null, label: catalogFault.label, notes: '' }]);
        setSelectedFaultId('');
    }

    function addCustomFault() {
        const label = customFault.trim();
        if (label === '' || revealedLabels.has(label)) return;
        changeDiagnosisField('revealed_faults', [...data.revealed_faults, { device_problem_tag_id: null, common_component_id: null, label, notes: '' }]);
        setCustomFault('');
    }

    function removeFault(label: string) {
        changeDiagnosisField('revealed_faults', data.revealed_faults.filter((fault) => fault.label !== label));
    }

    function saveResult(e: FormEvent) {
        e.preventDefault();
        router.post(
            `/admin/repairs/diagnosis-cases/${diagnosisCase.id}/result`,
            {
                outcome: data.outcome,
                diagnosis_notes: data.diagnosis_notes,
                diagnosis_fee_minor: data.diagnosis_fee_naira ? Math.round(parseFloat(data.diagnosis_fee_naira) * 100) : null,
                revealed_faults: data.revealed_faults,
            },
            {
                preserveScroll: true,
                onSuccess: () => setDiagnosisSaved(true),
            },
        );
    }

    function collectFee(e: FormEvent) {
        e.preventDefault();
        setCollectingFee(true);
        router.post(
            `/admin/repairs/diagnosis-cases/${diagnosisCase.id}/fee/collect`,
            { method: feeMethod },
            { preserveScroll: true, onFinish: () => setCollectingFee(false) },
        );
    }

    function createRepair() {
        if (openRepair) {
            router.visit(`/admin/repairs/${openRepair.id}`);

            return;
        }

        router.post(
            `/admin/repairs/diagnosis-cases/${diagnosisCase.id}/repairs`,
            { fault_ids: diagnosisCase.revealed_faults.map((fault) => fault.id) },
        );
    }

    return (
        <AdminShell>
            <Head title={`${diagnosisCase.device_make} ${diagnosisCase.device_model} diagnosis`} />
            <AdminPageHead
                title={`${diagnosisCase.device_make} ${diagnosisCase.device_model} diagnosis`}
                description={(diagnosisCase.customer_name ?? diagnosisCase.walk_in_name) ? `${diagnosisCase.customer_name ?? diagnosisCase.walk_in_name} - ${shop?.name ?? 'Unknown shop'}` : shop?.name ?? 'Unknown shop'}
            />

            <div className="mb-4 flex flex-wrap items-center gap-2">
                <AdminBadge status={diagnosisCase.status === 'called_up' ? 'success' : diagnosisCase.status === 'diagnosed' ? 'info' : 'neutral'}>
                    {diagnosisCase.status.replace(/_/g, ' ')}
                </AdminBadge>
                <Link href={`/admin/repairs/diagnosis-cases/${diagnosisCase.id}/docket`} className="text-sm font-semibold text-admin-blue">Print docket</Link>
            </div>

            <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_380px] items-start max-w-5xl">
                <div className="bg-admin-surface border border-admin-border rounded-admin-card p-6">
                    <div className="mb-4 pb-4 border-b border-admin-border">
                        <div className="text-sm text-admin-text2 mb-1">Reported issue</div>
                        <p className="text-sm text-admin-text whitespace-pre-wrap">{diagnosisCase.reported_issue ?? 'No issue recorded'}</p>
                    </div>

                    <Row label="Customer" value={diagnosisCase.customer_name ?? diagnosisCase.walk_in_name ?? 'Unknown customer'} />
                    {!diagnosisCase.customer_name && (diagnosisCase.walk_in_phone || diagnosisCase.walk_in_email) && (
                        <Row label="Contact" value={[diagnosisCase.walk_in_phone, diagnosisCase.walk_in_email].filter(Boolean).join(' · ')} />
                    )}
                    <Row label="Shop" value={shop?.name ?? 'Unknown shop'} />
                    <Row label="Device" value={`${diagnosisCase.device_make} ${diagnosisCase.device_model}`} />
                    <Row label="IMEI / serial" value={diagnosisCase.device_imei_serial ?? 'Not recorded'} />
                    <Row label="Diagnosis technician" value={assignedTechnicianName} />
                    <Row label="Device lock" value={!diagnosisCase.device_lock_present ? 'None set' : diagnosisCase.device_lock_visible_to_you ? diagnosisCase.device_lock_value : 'Set - hidden from you'} />
                    <Row
                        label="Diagnosis fee"
                        value={
                            diagnosisCase.diagnosis_fee_minor !== null
                                ? diagnosisCase.diagnosis_fee_paid
                                    ? `${formatNaira(diagnosisCase.diagnosis_fee_minor)} — Paid via ${diagnosisCase.diagnosis_fee_payment_method?.replace('_', ' ') ?? 'unknown method'}`
                                    : `${formatNaira(diagnosisCase.diagnosis_fee_minor)} — unpaid`
                                : 'Not set'
                        }
                    />

                    <div className="mt-5">
                        <div className="text-sm font-semibold text-admin-text mb-2">Revealed faults</div>
                        {diagnosisCase.revealed_faults.length === 0 ? (
                            <p className="text-sm text-admin-text3">No revealed faults saved yet.</p>
                        ) : (
                            <ul className="flex flex-col gap-2">
                                {diagnosisCase.revealed_faults.map((fault) => (
                                    <li key={fault.id} className="border border-admin-border rounded-admin-card px-3 py-2">
                                        <div className="flex items-center gap-2">
                                            <span className="text-sm font-semibold text-admin-text">{fault.label}</span>
                                            <FaultOriginBadge device_problem_tag_id={fault.device_problem_tag_id} common_component_id={fault.common_component_id} />
                                        </div>
                                        {fault.notes && <p className="text-xs text-admin-text2 mt-1">{fault.notes}</p>}
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>

                    {relatedRepairs.length > 0 && (
                        <div className="mt-5 pt-5 border-t border-admin-border">
                            <div className="text-sm font-semibold text-admin-text mb-2">Linked repair work orders</div>
                            <div className="flex flex-col gap-2">
                                {relatedRepairs.map((repair) => (
                                    <Link key={repair.id} href={`/admin/repairs/${repair.id}`} className="text-sm text-admin-blue font-semibold">
                                        {repair.display_label} - {repair.status.replace(/_/g, ' ')} - opened {repair.opened_at}
                                    </Link>
                                ))}
                            </div>
                        </div>
                    )}
                </div>

                <div className="bg-admin-surface border border-admin-border rounded-admin-card p-6">
                    <h2 className="text-base font-semibold text-admin-text mb-4">Actions</h2>

                    <form onSubmit={assignTechnician} className="flex flex-col gap-2 pb-4 border-b border-admin-border">
                        <label className="text-xs font-semibold text-admin-text">Assign diagnosis technician</label>
                        <select value={technicianId} onChange={(e) => setTechnicianId(e.target.value)} className="border border-admin-border-strong rounded-admin-button px-3 py-2 text-sm bg-white">
                            <option value="">Select technician...</option>
                            {technicians.map((technician) => <option key={technician.id} value={technician.id}>{technician.name}</option>)}
                        </select>
                        <AdminButton type="submit" variant="neutral" disabled={!technicianId}>Assign</AdminButton>
                    </form>

                    <form onSubmit={saveResult} className="flex flex-col gap-3 py-4 border-b border-admin-border">
                        <label className="text-xs font-semibold text-admin-text">Diagnosis outcome</label>
                        <select value={data.outcome} onChange={(e) => changeDiagnosisField('outcome', e.target.value)} className="border border-admin-border-strong rounded-admin-button px-3 py-2 text-sm bg-white">
                            <option value="repairable">Repairable</option>
                            <option value="unrepairable">Unrepairable</option>
                            <option value="requires_further_assessment">Requires further assessment</option>
                        </select>
                        {errors.outcome && <p className="text-xs text-admin-red">{errors.outcome}</p>}

                        <label className="text-xs font-semibold text-admin-text">Revealed faults</label>
                        <div className="flex gap-2">
                            <select value={selectedFaultId} onChange={(e) => setSelectedFaultId(e.target.value)} className="min-w-0 flex-1 border border-admin-border-strong rounded-admin-button px-3 py-2 text-sm bg-white">
                                <option value="">Select fault...</option>
                                {faultCatalog.map((fault) => <option key={fault.id} value={fault.id}>{fault.label}</option>)}
                            </select>
                            <AdminButton type="button" variant="neutral" onClick={addFault} disabled={!selectedFaultId}>Add</AdminButton>
                        </div>
                        <div className="flex gap-2">
                            <input
                                value={customFault}
                                onChange={(e) => setCustomFault(e.target.value)}
                                placeholder="Add unique revealed fault..."
                                className="min-w-0 flex-1 border border-admin-border-strong rounded-admin-button px-3 py-2 text-sm"
                            />
                            <AdminButton type="button" variant="neutral" onClick={addCustomFault} disabled={customFault.trim() === ''}>Add</AdminButton>
                        </div>
                        <div className="flex flex-wrap gap-1.5">
                            {data.revealed_faults.map((fault) => (
                                <button key={fault.label} type="button" onClick={() => removeFault(fault.label)} className="bg-admin-blue-soft text-admin-blue rounded-admin-badge px-2 py-1 text-xs font-semibold">
                                    {fault.label} x
                                </button>
                            ))}
                        </div>

                        <input type="number" step="0.01" value={data.diagnosis_fee_naira} onChange={(e) => changeDiagnosisField('diagnosis_fee_naira', e.target.value)} placeholder="Diagnosis fee (NGN)" className="border border-admin-border-strong rounded-admin-button px-3 py-2 text-sm" />
                        <textarea value={data.diagnosis_notes} onChange={(e) => changeDiagnosisField('diagnosis_notes', e.target.value)} rows={3} placeholder="Diagnosis notes" className="border border-admin-border-strong rounded-admin-button px-3 py-2 text-sm" />
                        <AdminButton type="submit" isLoading={processing} variant={diagnosisSaved ? 'neutral' : 'primary'}>
                            {diagnosisSaved ? 'Diagnosis saved' : 'Save diagnosis'}
                        </AdminButton>
                    </form>

                    {diagnosisCase.diagnosis_fee_minor !== null && !diagnosisCase.diagnosis_fee_paid && diagnosisCase.status === 'diagnosed' && (
                        <form onSubmit={collectFee} className="flex flex-col gap-2 py-4 border-b border-admin-border">
                            <label className="text-xs font-semibold text-admin-text">
                                Collect diagnosis fee ({formatNaira(diagnosisCase.diagnosis_fee_minor)})
                            </label>
                            <select value={feeMethod} onChange={(e) => setFeeMethod(e.target.value)} className="border border-admin-border-strong rounded-admin-button px-3 py-2 text-sm bg-white">
                                <option value="cash">Cash</option>
                                <option value="pos_terminal">POS terminal</option>
                                <option value="bank_transfer">Bank transfer</option>
                            </select>
                            <AdminButton type="submit" variant="neutral" isLoading={collectingFee}>
                                Collect diagnosis fee
                            </AdminButton>
                        </form>
                    )}

                    <div className="flex flex-col gap-2 pt-4">
                        {openRepair && (
                            <p className="text-xs text-admin-text3">
                                An open repair work order already exists: {openRepair.display_label} ({openRepair.status.replace(/_/g, ' ')}).
                            </p>
                        )}
                        <AdminButton onClick={createRepair} disabled={diagnosisCase.status === 'received'}>
                            {openRepair ? 'Open existing repair work' : hasCompletedRepair ? 'Open new repair work for this diagnosis' : 'Create repair from diagnosis'}
                        </AdminButton>
                        <AdminButton variant="neutral" onClick={() => router.visit(`/admin/repairs/diagnosis-cases/${diagnosisCase.id}/docket`)}>
                            Print docket
                        </AdminButton>
                    </div>
                </div>
            </div>
        </AdminShell>
    );
}
