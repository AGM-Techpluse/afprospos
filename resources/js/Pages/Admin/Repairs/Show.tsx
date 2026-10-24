import { FormEvent, useState } from 'react';
import { Head, router, usePoll } from '@inertiajs/react';
import { useConfirm } from '../../../hooks/useConfirm';
import AdminShell from '../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../Components/Admin/AdminPageHead';
import AdminSectionCard from '../../../Components/Admin/AdminSectionCard';
import AdminButton from '../../../Components/Admin/AdminButton';
import RepairStatusStepper from '../../../Features/Repairs/RepairStatusStepper';
import AdminBadge from '../../../Components/Admin/AdminBadge';
import DeviceLockInput, { DeviceLockType } from '../../../Features/Repairs/DeviceLockInput';
import PatternLockPad from '../../../Features/Repairs/PatternLockPad';
import RepairPhotoGallery, { RepairPhoto } from '../../../Features/Repairs/RepairPhotoGallery';
import { PartReservationRow } from '../../../Features/Repairs/PartsReservationPicker';
import FaultOriginBadge from '../../../Features/Repairs/FaultOriginBadge';
import FaultPicker, { FaultSearchResult } from '../../../Features/Repairs/FaultPicker';
import SuggestedPartsForFaults from '../../../Features/Repairs/SuggestedPartsForFaults';
import { formatNaira } from '../../../lib/money';

type RepairDetail = {
    id: number;
    shop_id: number;
    customer_id: number;
    customer_name: string | null;
    customer_phone: string | null;
    customer_email: string | null;
    walk_in_name: string | null;
    walk_in_phone: string | null;
    walk_in_email: string | null;
    device_make: string;
    device_model: string;
    reported_issue: string | null;
    device_imei_serial: string | null;
    device_lock_type: DeviceLockType;
    device_lock_present: boolean;
    device_lock_value: string | null;
    device_lock_visible_to_you: boolean;
    problem_tags: { id: number; label: string }[];
    photos: RepairPhoto[];
    technician_staff_id: number | null;
    labour_charge_minor: number | null;
    repair_cost_minor: number | null;
    diagnosis_fee: {
        amount_minor: number;
        decision: 'pending' | 'approved' | 'declined';
        paid: boolean;
        payment_status: string | null;
        method: string | null;
        outstanding_minor: number;
    } | null;
    quote_total_minor: number | null;
    diagnosis_conclusions: { outcome: string }[];
    diagnosis_case_id: number | null;
    reopened_from_repair_job_id: number | null;
    diagnosis_case: { id: number; status: string; outcome: string | null } | null;
    revealed_faults: { id: number; device_problem_tag_id: number | null; common_component_id: number | null; label: string; notes: string | null }[];
    fixed_faults: { id: number; diagnosis_case_fault_id: number | null; device_problem_tag_id: number | null; common_component_id: number | null; label: string; notes: string | null; commission_amount_minor: number | null }[];
    part_reservations: PartReservationRow[];
    down_payment_required_minor: number | null;
    down_payment_paid_minor: number;
    final_payment_paid_minor: number;
    outstanding_balance_minor: number | null;
    down_payment_deadline_at: string | null;
    failure_service_charge_minor: number | null;
    failure_refund_due_minor: number | null;
    repair_status: string;
    financial_status: string;
    unrepairable_settlement_state: string | null;
    resolution_notes: string | null;
    created_at: string;
    collection_case: { id: number; status: string; context: string; collection_deadline_at: string } | null;
    pending_payment_approval: { id: number; requested_financial_status: string; amount_minor: number; method: string } | null;
    pending_down_payment_approval: { id: number; amount_minor: number; method: string } | null;
    supplemental_quotes: { id: number; amount_minor: number; reason: string; status: string; requested_by_staff_id: number; decided_at: string | null }[];
    pending_supplemental_quote: { id: number; amount_minor: number; reason: string; requested_by_staff_id: number } | null;
};

type Technician = { id: number; name: string };
type Shop = { id: number; name: string };

interface RepairShowProps {
    repair: RepairDetail;
    shop: Shop | null;
    shops: Shop[];
    technicians: Technician[];
    canApprovePayment: boolean;
    canAuthorize: boolean;
    canTransferRepair: boolean;
}

function DetailRow({ label, value }: { label: string; value: React.ReactNode }) {
    return (
        <div className="flex items-center justify-between py-2.5 border-b border-admin-border last:border-b-0">
            <span className="text-sm text-admin-text2">{label}</span>
            <span className="text-sm font-medium text-admin-text">{value}</span>
        </div>
    );
}

const CLOSED_STATUSES = ['completed', 'unrepairable', 'expired_cancelled'];
/** Mirrors RepairJob::assertPartsReservable()'s exact status gate. */
const PARTS_RESERVABLE_STATUSES = ['diagnosing', 'awaiting_authorization', 'awaiting_parts', 'in_progress'];
/** Mirrors RepairJob::setDeviceLock()'s exact status gate — editable up through active work, locked once the device is ready for or past handover. */
const DEVICE_LOCK_EDITABLE_STATUSES_EXCLUDED = ['handover', 'completed', 'cancelled', 'unrepairable', 'expired_cancelled'];
/** Mirrors RepairJob::assertSupplementalQuoteRequestable()'s exact status gate. */
const SUPPLEMENTAL_QUOTE_REQUESTABLE_STATUSES = ['in_progress', 'awaiting_parts', 'qc_pending', 'handover'];

export default function RepairShow({ repair, shop, shops, technicians, canApprovePayment, canAuthorize, canTransferRepair }: RepairShowProps) {
    const confirm = useConfirm();
    // Multiple staff act on the same repair from different desks (frontdesk
    // collects payment, Manager sets requirements/approves) — poll just the
    // `repair` prop so each side sees the other's updates without a manual
    // refresh. Inertia pauses this automatically while the tab isn't visible.
    usePoll(5000, { only: ['repair'] });
    const [processing, setProcessing] = useState(false);
    const [downPayment, setDownPayment] = useState('');
    const [resolutionNotes, setResolutionNotes] = useState('');
    const [requestedFinancialStatus, setRequestedFinancialStatus] = useState('fully_paid');
    const [requestedAmount, setRequestedAmount] = useState('');
    const [requestedPaymentMethod, setRequestedPaymentMethod] = useState('cash');
    const [failReason, setFailReason] = useState('');
    const [serviceCharge, setServiceCharge] = useState('');
    const [settlementState, setSettlementState] = useState('pending_decision');
    const [technicianId, setTechnicianId] = useState(repair.technician_staff_id?.toString() ?? '');
    const [editingLock, setEditingLock] = useState(false);
    const [lockType, setLockType] = useState<DeviceLockType>(repair.device_lock_type);
    const [lockValue, setLockValue] = useState(repair.device_lock_value ?? '');
    const [qcOutcome, setQcOutcome] = useState<'passed' | 'failed' | 'not_testable'>('passed');
    const [qcNotes, setQcNotes] = useState('');
    const [cancelReason, setCancelReason] = useState('');
    const [transferShopId, setTransferShopId] = useState('');
    const [transferReason, setTransferReason] = useState('');
    const [releaseReservedParts, setReleaseReservedParts] = useState(false);
    const [feeNaira, setFeeNaira] = useState('');
    const [feeMethod, setFeeMethod] = useState('cash');
    const [quoteNaira, setQuoteNaira] = useState(repair.repair_cost_minor !== null ? String(repair.repair_cost_minor / 100) : '');
    const [diagnosisFeeNaira, setDiagnosisFeeNaira] = useState(repair.diagnosis_fee !== null ? String(repair.diagnosis_fee.amount_minor / 100) : '');
    const [hasAgreement, setHasAgreement] = useState(false);
    const [agreementNote, setAgreementNote] = useState('');
    const [requireDownPayment, setRequireDownPayment] = useState(false);
    const [downPaymentMethod, setDownPaymentMethod] = useState('cash');
    type SelectedFixedFault = { device_problem_tag_id: number; label: string; commission_naira: string };
    const [selectedFixedFaults, setSelectedFixedFaults] = useState<SelectedFixedFault[]>(
        repair.fixed_faults
            .filter((fault): fault is typeof fault & { device_problem_tag_id: number } => fault.device_problem_tag_id !== null)
            .map((fault) => ({
                device_problem_tag_id: fault.device_problem_tag_id,
                label: fault.label,
                commission_naira: fault.commission_amount_minor != null ? String(fault.commission_amount_minor / 100) : '',
            })),
    );
    const [customFixedFault, setCustomFixedFault] = useState('');
    const [customFixedFaultCommissionNaira, setCustomFixedFaultCommissionNaira] = useState('');
    const [supplementalAmountNaira, setSupplementalAmountNaira] = useState('');
    const [supplementalReason, setSupplementalReason] = useState('');
    const [addingCustomFixedFault, setAddingCustomFixedFault] = useState(false);

    function post(url: string, data: Record<string, string> = {}) {
        setProcessing(true);
        router.post(url, data, { onFinish: () => setProcessing(false) });
    }

    function saveDeviceLock(e: FormEvent) {
        e.preventDefault();
        setProcessing(true);
        router.post(
            `/admin/repairs/${repair.id}/device-lock`,
            { device_lock_type: lockType, device_lock_value: lockValue },
            { onFinish: () => setProcessing(false), onSuccess: () => setEditingLock(false) },
        );
    }

    function assignTechnician(e: FormEvent) {
        e.preventDefault();
        if (!technicianId) return;
        post(`/admin/repairs/${repair.id}/assign-technician`, { technician_staff_id: technicianId });
    }

    function transferRepair(e: FormEvent) {
        e.preventDefault();
        if (!transferShopId) return;
        post(`/admin/repairs/${repair.id}/transfer`, {
            target_shop_id: transferShopId,
            release_reserved_parts: releaseReservedParts ? '1' : '0',
            reason: transferReason,
        });
    }

    const assignedTechnicianName = technicians.find((t) => t.id === repair.technician_staff_id)?.name;
    const diagnosisFee = repair.diagnosis_fee;
    const reservedPartsCount = repair.part_reservations.filter((part) => part.status === 'reserved').length;
    const canShowRepairTransfer =
        canTransferRepair &&
        ['awaiting_authorization', 'payment_overdue', 'awaiting_parts', 'in_progress', 'failed_requires_resolution'].includes(
            repair.repair_status,
        );
    const transferTargetShops = shops.filter((availableShop) => availableShop.id !== repair.shop_id);
    const quoteDiagnosisFeeMinor =
        repair.diagnosis_fee?.decision === 'declined' ? 0 : Math.round(parseFloat(diagnosisFeeNaira || '0') * 100);
    const repairWorkLabel = `${repair.device_make} ${repair.device_model} repair work`;
    const diagnosisCaseLabel = `${repair.device_make} ${repair.device_model} diagnosis`;

    /** Manager sets the quote, down payment requirement, diagnosis fee, and any special agreement in one action. */
    function authorize(e: FormEvent) {
        e.preventDefault();
        post(`/admin/repairs/${repair.id}/authorize`, {
            repair_cost_minor: String(Math.round(parseFloat(quoteNaira || '0') * 100)),
            down_payment_required_minor: requireDownPayment ? String(Math.round(parseFloat(downPayment || '0') * 100)) : '0',
            down_payment_method: requireDownPayment ? downPaymentMethod : '',
            diagnosis_fee_minor: diagnosisFeeNaira ? String(Math.round(parseFloat(diagnosisFeeNaira || '0') * 100)) : '',
            special_agreement_note: hasAgreement ? agreementNote : '',
        });
    }

    async function declineQuote() {
        const confirmed = await confirm({
            kind: 'danger',
            title: 'Decline this quote?',
            body: 'The repair will be cancelled and any reserved parts go back to stock.',
            confirmLabel: 'Decline quote',
        });

        if (confirmed) {
            post(`/admin/repairs/${repair.id}/quote/decline`);
        }
    }

    function collectDiagnosisFee(e: FormEvent) {
        e.preventDefault();
        post(`/admin/repairs/${repair.id}/diagnosis-fee/collect`, { method: feeMethod });
    }

    function completeRepair(e: FormEvent) {
        e.preventDefault();
        setProcessing(true);

        type FixedFaultPayload = { diagnosis_case_fault_id: number | null; device_problem_tag_id: number | null; common_component_id: number | null; label: string; notes: string; commission_amount_minor: number | null };

        router.post(
            `/admin/repairs/${repair.id}/complete`,
            {
                resolution_notes: resolutionNotes,
                fixed_faults: selectedFixedFaults.map((fault): FixedFaultPayload => ({
                    diagnosis_case_fault_id: null,
                    device_problem_tag_id: fault.device_problem_tag_id,
                    common_component_id: null,
                    label: fault.label,
                    notes: '',
                    commission_amount_minor: fault.commission_naira.trim() !== '' ? Math.round(parseFloat(fault.commission_naira) * 100) : null,
                })),
            },
            { onFinish: () => setProcessing(false) },
        );
    }

    function addSelectedFixedFault(fault: { id: number; label: string; commission_amount_minor?: number | null }) {
        setSelectedFixedFaults((current) => {
            if (current.some((f) => f.device_problem_tag_id === fault.id)) return current;
            return [...current, {
                device_problem_tag_id: fault.id,
                label: fault.label,
                commission_naira: fault.commission_amount_minor != null ? String(fault.commission_amount_minor / 100) : '',
            }];
        });
    }

    function removeSelectedFixedFault(deviceProblemTagId: number) {
        setSelectedFixedFaults((current) => current.filter((f) => f.device_problem_tag_id !== deviceProblemTagId));
    }

    function updateSelectedFixedFaultCommission(deviceProblemTagId: number, commissionNaira: string) {
        setSelectedFixedFaults((current) => current.map((f) => (f.device_problem_tag_id === deviceProblemTagId ? { ...f, commission_naira: commissionNaira } : f)));
    }

    /** For a fault the catalog doesn't have recorded yet — joins it (scoped to this repair's own device model) with the commission set here, then selects it, same as picking an existing one. */
    function addNewDiscoveredFixedFault() {
        const label = customFixedFault.trim();
        if (label === '') return;

        setAddingCustomFixedFault(true);
        fetch(`/admin/repairs/${repair.id}/faults`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-XSRF-TOKEN': decodeURIComponent(document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/)?.[1] ?? ''),
            },
            body: JSON.stringify({
                label,
                commission_amount_minor: customFixedFaultCommissionNaira.trim() !== '' ? Math.round(parseFloat(customFixedFaultCommissionNaira) * 100) : undefined,
            }),
        })
            .then((response) => response.json())
            .then((json: { fault: { id: number; label: string; commission_amount_minor: number | null } }) => {
                addSelectedFixedFault(json.fault);
                setCustomFixedFault('');
                setCustomFixedFaultCommissionNaira('');
            })
            .finally(() => setAddingCustomFixedFault(false));
    }

    /** QC pass, not testable, or fail. A fail follows the shop's configured policy (return to diagnosis, or cancel). */
    function recordQualityCheck(e: FormEvent) {
        e.preventDefault();
        post(`/admin/repairs/${repair.id}/quality-check`, { outcome: qcOutcome, notes: qcNotes });
    }

    async function cancelRepair() {
        const confirmed = await confirm({
            kind: 'danger',
            title: 'Cancel this repair?',
            body: 'Any reserved parts go back to stock. The job cannot be reopened.',
            confirmLabel: 'Cancel repair',
        });

        if (confirmed) {
            post(`/admin/repairs/${repair.id}/cancel`, { reason: cancelReason });
        }
    }

    /** A separate payment for the diagnosis. It is not part of the repair quote and does not change the repair. */
    function requestDiagnosisFee(e: FormEvent) {
        e.preventDefault();
        post(`/admin/repairs/${repair.id}/diagnosis-fee`, {
            amount_minor: String(Math.round(parseFloat(feeNaira || '0') * 100)),
            method: feeMethod,
        });
    }

    function requestPaymentApproval(e: FormEvent) {
        e.preventDefault();
        post(`/admin/repairs/${repair.id}/payment-approval`, {
            financial_status: requestedFinancialStatus,
            amount_minor: String(Math.round(parseFloat(requestedAmount || '0') * 100)),
            method: requestedPaymentMethod,
        });
    }

    function decidePaymentApproval(approvalId: number, approved: boolean) {
        post(`/admin/repairs/${repair.id}/payment-approval/${approvalId}/decide`, { approved: approved ? '1' : '0' });
    }

    function requestSupplementalQuote(e: FormEvent) {
        e.preventDefault();
        post(`/admin/repairs/${repair.id}/supplemental-quote`, {
            amount_minor: String(Math.round(parseFloat(supplementalAmountNaira || '0') * 100)),
            reason: supplementalReason,
        });
    }

    function decideSupplementalQuote(quoteId: number, approved: boolean) {
        post(`/admin/repairs/${repair.id}/supplemental-quote/${quoteId}/decide`, { approved: approved ? '1' : '0' });
    }

    async function failRepair(e: FormEvent) {
        e.preventDefault();
        post(`/admin/repairs/${repair.id}/fail`, {
            reason: failReason,
            service_charge_minor: String(Math.round(parseFloat(serviceCharge || '0') * 100)),
        });
    }

    function markUnrepairable(e: FormEvent) {
        e.preventDefault();
        post(`/admin/repairs/${repair.id}/unrepairable`, { settlement_state: settlementState });
    }

    async function resume() {
        const confirmed = await confirm({
            kind: 'info',
            title: 'Resume this repair?',
            body: 'This moves the job back to in progress.',
            confirmLabel: 'Resume',
        });
        if (confirmed) post(`/admin/repairs/${repair.id}/resume`);
    }

    return (
        <AdminShell>
            <Head title={repairWorkLabel} />

            <AdminPageHead
                title={`${repair.device_make} ${repair.device_model}`}
                description={`${repair.customer_name ?? repair.walk_in_name ?? 'Customer'} — ${repair.repair_status.replace(/_/g, ' ')}`}
            />

            <div className="divide-y divide-admin-border">
            <div className="pb-6 flex flex-wrap items-center gap-3">
                <RepairStatusStepper status={repair.repair_status} />
                {repair.repair_status === 'completed' && (
                    <AdminBadge status={repair.financial_status === 'fully_paid' ? 'success' : 'warning'}>
                        {repair.financial_status === 'fully_paid' ? 'Payment received' : `Payment ${repair.financial_status.replace(/_/g, ' ')}`}
                    </AdminBadge>
                )}
                {repair.financial_status === 'fully_paid' && (
                    <AdminButton variant="neutral" onClick={() => router.visit(`/admin/repairs/${repair.id}/receipt`)}>
                        View receipt
                    </AdminButton>
                )}
            </div>

            <div className="py-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px] items-start max-w-4xl">
                <AdminSectionCard title="Details">
                <div className="p-6">
                    {(repair.reported_issue || repair.problem_tags.length > 0) && (
                        <div className="mb-4 pb-4 border-b border-admin-border">
                            <div className="text-sm text-admin-text2 mb-1">Reported issue</div>
                            {repair.problem_tags.length > 0 && (
                                <div className="flex flex-wrap gap-1.5 mb-1.5">
                                    {repair.problem_tags.map((tag) => (
                                        <span key={tag.id} className="bg-admin-blue-soft text-admin-blue text-xs font-semibold rounded-admin-badge px-2 py-1">
                                            {tag.label}
                                        </span>
                                    ))}
                                </div>
                            )}
                            {repair.reported_issue && <p className="text-sm text-admin-text whitespace-pre-wrap">{repair.reported_issue}</p>}
                        </div>
                    )}

                    <div className="mb-4 pb-4 border-b border-admin-border">
                        <div className="text-sm text-admin-text2 mb-1">Customer</div>
                        <p className="text-sm font-medium text-admin-text">{repair.customer_name ?? repair.walk_in_name ?? 'Unknown customer'}</p>
                        {repair.customer_phone && <p className="text-sm text-admin-text2">{repair.customer_phone}</p>}
                        {repair.customer_email && <p className="text-sm text-admin-text2">{repair.customer_email}</p>}
                        {!repair.customer_name && repair.walk_in_phone && <p className="text-sm text-admin-text2">{repair.walk_in_phone}</p>}
                        {!repair.customer_name && repair.walk_in_email && <p className="text-sm text-admin-text2">{repair.walk_in_email}</p>}
                    </div>

                    <DetailRow label="Device" value={`${repair.device_make} ${repair.device_model}`} />
                    <DetailRow label="Shop" value={shop ? shop.name : 'Unknown shop'} />
                    <DetailRow label="IMEI / serial" value={repair.device_imei_serial ?? 'Not recorded'} />
                    {repair.diagnosis_case && (
                        <DetailRow
                            label="Diagnosis case"
                            value={
                                <button
                                    type="button"
                                    onClick={() => router.visit(`/admin/repairs/diagnosis-cases/${repair.diagnosis_case!.id}`)}
                                    className="font-semibold text-admin-blue"
                                >
                                    {diagnosisCaseLabel} · {repair.diagnosis_case.status.replace(/_/g, ' ')}
                                </button>
                            }
                        />
                    )}
                    {repair.reopened_from_repair_job_id && (
                        <DetailRow
                            label="Reopened from"
                            value={
                                <button
                                    type="button"
                                    onClick={() => router.visit(`/admin/repairs/${repair.reopened_from_repair_job_id}`)}
                                    className="font-semibold text-admin-blue"
                                >
                                    Previous repair work
                                </button>
                            }
                        />
                    )}

                    <div className="py-2.5 border-b border-admin-border">
                        <div className="flex items-center justify-between mb-1.5">
                            <span className="text-sm text-admin-text2">Device lock</span>
                            {!editingLock && !DEVICE_LOCK_EDITABLE_STATUSES_EXCLUDED.includes(repair.repair_status) && (
                                <button type="button" onClick={() => setEditingLock(true)} className="text-xs font-semibold text-admin-blue">
                                    {repair.device_lock_present ? 'Edit' : 'Add'}
                                </button>
                            )}
                        </div>

                        {editingLock ? (
                            <form onSubmit={saveDeviceLock}>
                                <DeviceLockInput lockType={lockType} lockValue={lockValue} onChange={(t, v) => { setLockType(t); setLockValue(v); }} />
                                <div className="flex items-center gap-2">
                                    <AdminButton type="submit" variant="neutral" isLoading={processing}>
                                        Save
                                    </AdminButton>
                                    <AdminButton
                                        type="button"
                                        variant="neutral"
                                        onClick={() => {
                                            setEditingLock(false);
                                            setLockType(repair.device_lock_type);
                                            setLockValue(repair.device_lock_value ?? '');
                                        }}
                                    >
                                        Cancel
                                    </AdminButton>
                                </div>
                            </form>
                        ) : !repair.device_lock_present ? (
                            <p className="text-sm text-admin-text3">None set</p>
                        ) : !repair.device_lock_visible_to_you ? (
                            <p className="text-sm text-admin-text3">Set — hidden (only the assigned technician or Shop Owner can view it)</p>
                        ) : repair.device_lock_type === 'code' ? (
                            <p className="text-sm font-mono font-medium text-admin-text">{repair.device_lock_value}</p>
                        ) : (
                            <PatternLockPad value={repair.device_lock_value?.split('-').map(Number) ?? []} onChange={() => {}} readOnly />
                        )}
                    </div>

                    <DetailRow
                        label="Repair cost"
                        value={repair.repair_cost_minor !== null ? formatNaira(repair.repair_cost_minor) : 'Not yet estimated'}
                    />
                    <DetailRow
                        label="Quote total"
                        value={repair.quote_total_minor !== null ? formatNaira(repair.quote_total_minor) : '—'}
                    />
                    {repair.supplemental_quotes.some((quote) => quote.status === 'approved') && (
                        <div className="py-3 border-b border-admin-border">
                            <div className="text-sm text-admin-text2 mb-2">Supplemental charges (added to the quote above)</div>
                            <ul className="flex flex-col gap-2 text-sm text-admin-text">
                                {repair.supplemental_quotes
                                    .filter((quote) => quote.status === 'approved')
                                    .map((quote) => (
                                        <li key={quote.id} className="flex justify-between gap-3">
                                            <span className="min-w-0">{quote.reason}</span>
                                            <span className="shrink-0 font-semibold">{formatNaira(quote.amount_minor)}</span>
                                        </li>
                                    ))}
                            </ul>
                        </div>
                    )}
                    {repair.part_reservations.some((p) => p.status === 'reserved') && (
                        <div className="py-3 border-b border-admin-border">
                            <div className="text-sm text-admin-text2 mb-2">Parts set aside</div>
                            <ul className="flex flex-col gap-1 text-sm text-admin-text">
                                {repair.part_reservations
                                    .filter((p) => p.status === 'reserved')
                                    .map((p) => (
                                        <li key={p.id} className="flex justify-between gap-3">
                                            <span className="min-w-0 truncate">
                                                {p.product_name ?? p.sku_code ?? `SKU ${p.sku_id}`} × {p.quantity}
                                            </span>
                                            <span className="shrink-0 font-semibold">
                                                {p.unit_price_minor !== null ? formatNaira(p.unit_price_minor * p.quantity) : '—'}
                                            </span>
                                        </li>
                                    ))}
                            </ul>
                        </div>
                    )}
                    {repair.part_reservations.some((p) => p.status === 'installed') && (
                        <div className="py-3 border-b border-admin-border">
                            <div className="text-sm text-admin-text2 mb-2">Parts installed</div>
                            <ul className="flex flex-col gap-1 text-sm text-admin-text">
                                {repair.part_reservations
                                    .filter((p) => p.status === 'installed')
                                    .map((p) => (
                                        <li key={p.id} className="flex justify-between gap-3">
                                            <span className="min-w-0 truncate">
                                                {p.product_name ?? p.sku_code ?? `SKU ${p.sku_id}`} × {p.quantity}
                                            </span>
                                            <span className="shrink-0 font-semibold">
                                                {p.unit_price_minor !== null ? formatNaira(p.unit_price_minor * p.quantity) : '—'}
                                            </span>
                                        </li>
                                    ))}
                            </ul>
                        </div>
                    )}
                    <DetailRow
                        label="Diagnosis fee"
                        value={repair.diagnosis_fee ? formatNaira(repair.diagnosis_fee.amount_minor) + (repair.diagnosis_fee.paid ? ' · paid' : ' · unpaid') : '—'}
                    />
                    <DetailRow
                        label="Labour charge"
                        value={repair.labour_charge_minor !== null ? formatNaira(repair.labour_charge_minor) : '—'}
                    />
                    <DetailRow
                        label="Down payment required"
                        value={repair.down_payment_required_minor !== null ? formatNaira(repair.down_payment_required_minor) : '—'}
                    />
                    {repair.down_payment_paid_minor > 0 && (
                        <DetailRow label="Down payment paid" value={formatNaira(repair.down_payment_paid_minor)} />
                    )}
                    {repair.down_payment_required_minor !== null && repair.down_payment_required_minor > repair.down_payment_paid_minor && repair.down_payment_paid_minor > 0 && (
                        <DetailRow label="Down payment remaining" value={formatNaira(repair.down_payment_required_minor - repair.down_payment_paid_minor)} />
                    )}
                    {repair.failure_service_charge_minor !== null && (
                        <DetailRow label="Failed repair service charge" value={formatNaira(repair.failure_service_charge_minor)} />
                    )}
                    {repair.failure_refund_due_minor !== null && (
                        <DetailRow label="Refund due" value={formatNaira(repair.failure_refund_due_minor)} />
                    )}
                    {repair.revealed_faults.length > 0 && (
                        <div className="py-3 border-b border-admin-border">
                            <div className="text-sm text-admin-text2 mb-2">Diagnosis revealed faults</div>
                            <div className="flex flex-wrap gap-1.5">
                                {repair.revealed_faults.map((fault) => (
                                    <span key={fault.id} className="inline-flex items-center gap-1.5 bg-admin-blue-soft text-admin-blue text-xs font-semibold rounded-admin-badge px-2 py-1">
                                        {fault.label}
                                        <FaultOriginBadge device_problem_tag_id={fault.device_problem_tag_id} common_component_id={fault.common_component_id} />
                                    </span>
                                ))}
                            </div>
                        </div>
                    )}
                    {repair.fixed_faults.length > 0 && (
                        <div className="py-3 border-b border-admin-border">
                            <div className="text-sm text-admin-text2 mb-2">Actually fixed faults</div>
                            <div className="flex flex-wrap gap-1.5">
                                {repair.fixed_faults.map((fault) => (
                                    <span key={fault.id} className="inline-flex items-center gap-1.5 bg-admin-green-soft text-admin-green-text text-xs font-semibold rounded-admin-badge px-2 py-1">
                                        {fault.label}
                                        <FaultOriginBadge device_problem_tag_id={fault.device_problem_tag_id} common_component_id={fault.common_component_id} />
                                    </span>
                                ))}
                            </div>
                        </div>
                    )}
                    <DetailRow
                        label="Authorization deadline"
                        value={repair.down_payment_deadline_at ? new Date(repair.down_payment_deadline_at).toLocaleString() : '—'}
                    />
                    <DetailRow label="Financial status" value={repair.financial_status.replace(/_/g, ' ')} />
                    {repair.unrepairable_settlement_state && (
                        <DetailRow label="Unrepairable settlement" value={repair.unrepairable_settlement_state.replace(/_/g, ' ')} />
                    )}
                    <DetailRow
                        label="Technician"
                        value={repair.technician_staff_id ? (assignedTechnicianName ?? 'Assigned technician') : 'Unassigned'}
                    />
                    <DetailRow label="Received" value={new Date(repair.created_at).toLocaleString()} />

                    {repair.resolution_notes && (
                        <div className="mt-4 pt-4 border-t border-admin-border">
                            <div className="text-sm text-admin-text2 mb-1">Resolution notes</div>
                            <p className="text-sm text-admin-text whitespace-pre-wrap">{repair.resolution_notes}</p>
                        </div>
                    )}

                    {repair.collection_case && (
                        <div className="mt-4 pt-4 border-t border-admin-border">
                            <button
                                type="button"
                                onClick={() => router.visit(`/admin/collection/${repair.collection_case!.id}`)}
                                className="text-sm font-semibold text-admin-blue"
                            >
                                View collection case ({repair.collection_case.status.replace(/_/g, ' ')}) →
                            </button>
                        </div>
                    )}

                </div>
                </AdminSectionCard>

                <div className="flex flex-col gap-2">
                    <h2 className="text-sm font-semibold text-admin-text mb-1">Actions</h2>

                    {false && ['diagnosing', 'unrepairable', 'awaiting_authorization', 'payment_overdue', 'cancelled'].includes(repair.repair_status) && (
                        <div className="flex flex-col gap-2 border-b border-admin-border pb-3 mb-1">
                            <span className="text-xs font-semibold text-admin-text">Diagnosis fee</span>
                            {!diagnosisFee ? (
                                <form onSubmit={requestDiagnosisFee} className="flex flex-col gap-2">
                                    <span className="text-xs text-admin-text2">
                                        Set this after diagnosis. It can be collected now, or the manager can adjust it in quote & authorization.
                                    </span>
                                    <input
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        value={feeNaira}
                                        onChange={(e) => setFeeNaira(e.target.value)}
                                        placeholder="Amount (₦)"
                                        className="border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs"
                                    />
                                    <AdminButton type="submit" variant="neutral" isLoading={processing} disabled={!feeNaira || parseFloat(feeNaira) <= 0}>
                                        Set diagnosis fee
                                    </AdminButton>
                                </form>
                            ) : diagnosisFee!.decision === 'declined' ? (
                                <span className="text-xs text-admin-text2">
                                    Diagnosis fee of {formatNaira(diagnosisFee!.amount_minor)} was declined. It is not due.
                                </span>
                            ) : diagnosisFee!.paid ? (
                                <span className="text-xs text-admin-green-text bg-admin-green-soft rounded-admin-button px-2 py-2">
                                    Diagnosis fee paid — {formatNaira(diagnosisFee!.amount_minor)} via{' '}
                                    {(diagnosisFee!.method ?? '').replace(/_/g, ' ')}
                                </span>
                            ) : (
                                <form onSubmit={collectDiagnosisFee} className="flex flex-col gap-2">
                                    <span className="text-xs text-admin-text2">
                                        {formatNaira(diagnosisFee!.amount_minor)} can be collected now, or it is added to the quote.
                                    </span>
                                    <select
                                        value={feeMethod}
                                        onChange={(e) => setFeeMethod(e.target.value)}
                                        className="border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs"
                                    >
                                        <option value="cash">Cash</option>
                                        <option value="pos_terminal">POS terminal</option>
                                        <option value="bank_transfer">Bank transfer</option>
                                    </select>
                                    <AdminButton type="submit" variant="neutral" isLoading={processing}>
                                        Collect diagnosis fee
                                    </AdminButton>
                                </form>
                            )}
                        </div>
                    )}
{PARTS_RESERVABLE_STATUSES.includes(repair.repair_status) && (
                        <div className="flex flex-col gap-2 border-b border-admin-border pb-3 mb-1">
                            <span className="text-xs font-semibold text-admin-text">Parts</span>
                            <span className="text-xs text-admin-text2">
                                Reserve the parts this repair needs, so the manager can price them into the quote.
                            </span>
                            <AdminButton variant="neutral" onClick={() => router.visit(`/admin/repairs/${repair.id}/parts`)}>
                                Manage parts
                            </AdminButton>
                        </div>
                    )}

                    {SUPPLEMENTAL_QUOTE_REQUESTABLE_STATUSES.includes(repair.repair_status) && (
                        <div className="flex flex-col gap-2 border-b border-admin-border pb-3 mb-1">
                            <span className="text-xs font-semibold text-admin-text">Supplemental quote</span>
                            {repair.pending_supplemental_quote ? (
                                <div className="flex flex-col gap-2 text-xs text-admin-text2 bg-ui-warning-soft text-ui-warning-text rounded-admin-button p-2">
                                    <span>
                                        {formatNaira(repair.pending_supplemental_quote.amount_minor)} requested — {repair.pending_supplemental_quote.reason}
                                    </span>
                                    {canAuthorize && (
                                        <div className="flex items-center gap-2">
                                            <AdminButton
                                                size="sm"
                                                onClick={() => decideSupplementalQuote(repair.pending_supplemental_quote!.id, true)}
                                                isLoading={processing}
                                            >
                                                Approve
                                            </AdminButton>
                                            <AdminButton
                                                size="sm"
                                                variant="neutral"
                                                onClick={() => decideSupplementalQuote(repair.pending_supplemental_quote!.id, false)}
                                                disabled={processing}
                                            >
                                                Reject
                                            </AdminButton>
                                        </div>
                                    )}
                                </div>
                            ) : (
                                <form onSubmit={requestSupplementalQuote} className="flex flex-col gap-2">
                                    <span className="text-xs text-admin-text2">
                                        Found more needed to finish this repair than the quote covers? Flag the extra amount here — it needs
                                        approval before it counts toward the outstanding balance.
                                    </span>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value={supplementalAmountNaira}
                                        onChange={(e) => setSupplementalAmountNaira(e.target.value)}
                                        placeholder="Additional amount (₦)"
                                        className="border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs"
                                    />
                                    <textarea
                                        value={supplementalReason}
                                        onChange={(e) => setSupplementalReason(e.target.value)}
                                        rows={2}
                                        placeholder="Why — e.g. battery, screen and charging port all needed replacing"
                                        className="border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs"
                                    />
                                    <AdminButton
                                        type="submit"
                                        variant="neutral"
                                        size="sm"
                                        isLoading={processing}
                                        disabled={!(parseFloat(supplementalAmountNaira) > 0) || supplementalReason.trim() === ''}
                                    >
                                        Request supplemental quote
                                    </AdminButton>
                                </form>
                            )}
                        </div>
                    )}

{false && repair.repair_status === 'diagnosing' && (
                        <div className="flex flex-col gap-2 border-b border-admin-border pb-3 mb-1">
                            <span className="text-xs text-admin-text2">
                                When the faulty parts and the outcome are saved, send the repair to the manager for the quote.
                            </span>
                            <AdminButton
                                onClick={() => post(`/admin/repairs/${repair.id}/send-to-quote`)}
                                isLoading={processing}
                                disabled={!repair.diagnosis_conclusions.some((c) => c.outcome === 'repairable')}
                            >
                                Send to quote & authorization
                            </AdminButton>
                            {!repair.diagnosis_conclusions.some((c) => c.outcome === 'repairable') && (
                                <span className="text-xs text-admin-text3">Save the diagnosis outcome as repairable first.</span>
                            )}
                        </div>
                    )}

                    {!CLOSED_STATUSES.includes(repair.repair_status) && !['received', 'diagnosing', 'qc_pending', 'handover'].includes(repair.repair_status) && (
                        <form onSubmit={assignTechnician} className="flex flex-col gap-2 border-b border-admin-border pb-3 mb-1">
                            <label className="text-xs font-semibold text-admin-text">
                                {repair.technician_staff_id ? 'Reassign technician' : 'Assign technician'}
                            </label>
                            <select
                                value={technicianId}
                                onChange={(e) => setTechnicianId(e.target.value)}
                                className="border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs"
                            >
                                <option value="">Select a technician…</option>
                                {technicians.map((technician) => (
                                    <option key={technician.id} value={technician.id}>
                                        {technician.name}
                                    </option>
                                ))}
                            </select>
                            <AdminButton type="submit" variant="neutral" isLoading={processing} disabled={!technicianId}>
                                {repair.technician_staff_id ? 'Reassign' : 'Assign'}
                            </AdminButton>
                        </form>
                    )}

{false && repair.repair_status === 'received' && (
                        <>
                            {/*
                            <p className="text-xs text-admin-text3">
                                Record a diagnosis to move this job forward — authorizing repair work, or closing it out as
                                unrepairable, both happen from the Diagnosis page.
                            </p>
                            */}
                            <AdminButton onClick={() => router.visit(`/admin/repairs/${repair.id}/diagnosis`)}>Proceed to diagnosis</AdminButton>
                        </>
                    )}

                    {false && repair.repair_status === 'diagnosing' && (
                        <AdminButton onClick={() => router.visit(`/admin/repairs/${repair.id}/diagnosis`)}>Diagnose</AdminButton>
                    )}

                    {['received', 'diagnosing'].includes(repair.repair_status) && (
                        <p className="text-xs text-admin-text3">
                            Diagnosis now lives under Diagnosis cases. Call up a saved diagnosis to create repair work.
                        </p>
                    )}

                    {(repair.repair_status === 'awaiting_authorization' || repair.repair_status === 'payment_overdue') && (
                        <>
                            <div className="flex flex-col gap-3 border-b border-admin-border pb-3 mb-1">
                                <span className="text-xs font-semibold text-admin-text">Quote & authorization</span>
                                {repair.part_reservations.filter((p) => p.status === 'reserved').length > 0 && (
                                    <div className="flex flex-col gap-1 text-xs text-admin-text2 border border-admin-border rounded-admin-button p-2">
                                        <span className="font-semibold text-admin-text">Parts set aside for this repair</span>
                                        {repair.part_reservations
                                            .filter((p) => p.status === 'reserved')
                                            .map((p) => (
                                                <div key={p.id} className="flex justify-between gap-2">
                                                    <span>
                                                        {p.product_name ?? p.sku_code ?? `SKU ${p.sku_id}`} × {p.quantity}
                                                    </span>
                                                    <span>{p.unit_price_minor !== null ? formatNaira(p.unit_price_minor * p.quantity) : '—'}</span>
                                                </div>
                                            ))}
                                        <div className="flex justify-between gap-2 font-semibold text-admin-text border-t border-admin-border pt-1 mt-1">
                                            <span>Parts value (include in the quote as you see fit)</span>
                                            <span>
                                                {formatNaira(
                                                    repair.part_reservations
                                                        .filter((p) => p.status === 'reserved')
                                                        .reduce((sum, p) => sum + (p.unit_price_minor ?? 0) * p.quantity, 0),
                                                )}
                                            </span>
                                        </div>
                                    </div>
                                )}
                                {canAuthorize ? (
                                    <form onSubmit={authorize} className="flex flex-col gap-3">
                                        <label className="text-xs text-admin-text2">Repair amount (₦)</label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={quoteNaira}
                                            onChange={(e) => setQuoteNaira(e.target.value)}
                                            placeholder="Amount (₦)"
                                            className="border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs"
                                        />
                                        <label className="text-xs text-admin-text2">Diagnosis fee (₦)</label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={diagnosisFeeNaira}
                                            onChange={(e) => setDiagnosisFeeNaira(e.target.value)}
                                            placeholder="Amount (₦)"
                                            disabled={repair.diagnosis_fee?.paid}
                                            className="border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs disabled:bg-admin-bg disabled:text-admin-text3"
                                        />
                                        {repair.diagnosis_fee?.paid ? (
                                            <span className="text-xs text-admin-text2">Diagnosis fee already paid, so it is not added to this quote.</span>
                                        ) : quoteDiagnosisFeeMinor > 0 && (
                                            <span className="text-xs text-admin-text2">
                                                Unpaid diagnosis fee of {formatNaira(quoteDiagnosisFeeMinor)} is added to the quote.
                                            </span>
                                        )}
                                        <label className="flex items-center gap-2 text-xs text-admin-text">
                                            <input type="checkbox" checked={requireDownPayment} onChange={(e) => setRequireDownPayment(e.target.checked)} />
                                            Down payment required to start
                                        </label>
                                        {requireDownPayment && (
                                            <>
                                            <input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                placeholder="Down payment (₦)"
                                                value={downPayment}
                                                onChange={(e) => setDownPayment(e.target.value)}
                                                className="border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs"
                                            />
                                            <select
                                                value={downPaymentMethod}
                                                onChange={(e) => setDownPaymentMethod(e.target.value)}
                                                className="border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs"
                                            >
                                                <option value="cash">Cash</option>
                                                <option value="pos_terminal">POS terminal</option>
                                                <option value="bank_transfer">Bank transfer</option>
                                            </select>
                                            </>
                                        )}
                                        <label className="flex items-center gap-2 text-xs text-admin-text">
                                            <input type="checkbox" checked={hasAgreement} onChange={(e) => setHasAgreement(e.target.checked)} />
                                            Special agreement (promo, policy exception, warranty arrangement)
                                        </label>
                                        {hasAgreement && (
                                            <textarea
                                                rows={2}
                                                value={agreementNote}
                                                onChange={(e) => setAgreementNote(e.target.value)}
                                                placeholder="What was agreed with the customer"
                                                className="border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs"
                                            />
                                        )}
                                        <div className="text-sm font-semibold text-admin-text">
                                            Quote total{' '}
                                            {formatNaira(
                                                Math.round(parseFloat(quoteNaira || '0') * 100) +
                                                    (repair.diagnosis_fee?.paid ? 0 : quoteDiagnosisFeeMinor),
                                            )}
                                        </div>
                                        <div className="flex gap-2">
                                            <AdminButton
                                                type="submit"
                                                isLoading={processing}
                                                disabled={!(parseFloat(quoteNaira) > 0) || (requireDownPayment && !(parseFloat(downPayment) > 0))}
                                            >
                                                {requireDownPayment ? 'Authorize and record down payment' : 'Authorize repair'}
                                            </AdminButton>
                                            <AdminButton type="button" variant="danger" onClick={declineQuote} disabled={processing}>
                                                Decline quote
                                            </AdminButton>
                                        </div>
                                    </form>
                                ) : (
                                    <p className="text-xs text-admin-text3">
                                        {repair.repair_cost_minor !== null
                                            ? `Quote of ${formatNaira(repair.repair_cost_minor)} waiting for the manager to authorize it.`
                                            : 'Waiting for the manager to set the quote and authorize the repair.'}
                                    </p>
                                )}
                            </div>

                        </>
                    )}


                    {repair.repair_status === 'in_progress' && (
                        <>
                            <form onSubmit={completeRepair} className="flex flex-col gap-2">
                                <div className="flex flex-col gap-1.5">
                                    <span className="text-xs font-semibold text-admin-text">Actually fixed faults</span>
                                    <p className="text-[11px] text-admin-text3 -mt-1">This device's own fixable faults — pick what was actually fixed, and adjust the commission per fault if needed.</p>
                                    <FaultPicker
                                        searchUrl={`/admin/repairs/${repair.id}/faults/search`}
                                        onSelect={addSelectedFixedFault}
                                        excludeLabels={selectedFixedFaults.map((fault) => fault.label)}
                                    />
                                    {selectedFixedFaults.length > 0 && (
                                        <div className="flex flex-col gap-1.5 rounded-admin-button border border-admin-border p-2">
                                            {selectedFixedFaults.map((fault) => (
                                                <div key={fault.device_problem_tag_id} className="flex items-center gap-2 text-xs text-admin-text">
                                                    <span className="flex-1 min-w-0 truncate">{fault.label}</span>
                                                    <span className="text-admin-text3">₦</span>
                                                    <input
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        value={fault.commission_naira}
                                                        onChange={(e) => updateSelectedFixedFaultCommission(fault.device_problem_tag_id, e.target.value)}
                                                        placeholder="0"
                                                        className="w-20 border border-admin-border-strong rounded-admin-button px-1.5 py-1 text-xs"
                                                    />
                                                    <button
                                                        type="button"
                                                        onClick={() => removeSelectedFixedFault(fault.device_problem_tag_id)}
                                                        className="text-admin-text3 hover:text-admin-red shrink-0"
                                                        aria-label={`Remove ${fault.label}`}
                                                    >
                                                        x
                                                    </button>
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                    <SuggestedPartsForFaults
                                        repairId={repair.id}
                                        tagIds={selectedFixedFaults.map((fault) => fault.device_problem_tag_id)}
                                        reservedSkuIds={repair.part_reservations.map((part) => part.sku_id)}
                                    />
                                </div>
                                <div className="flex flex-col gap-1.5">
                                    <span className="text-xs font-semibold text-admin-text">Add newly discovered fixed fault</span>
                                    <p className="text-[11px] text-admin-text3 -mt-1">Only for a fault this device's catalog doesn't already have recorded.</p>
                                    <div className="flex gap-2">
                                        <input
                                            value={customFixedFault}
                                            onChange={(e) => setCustomFixedFault(e.target.value)}
                                            placeholder="e.g. loose display connector"
                                            className="min-w-0 flex-1 border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs"
                                        />
                                        <span className="self-center text-admin-text3 text-xs">₦</span>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={customFixedFaultCommissionNaira}
                                            onChange={(e) => setCustomFixedFaultCommissionNaira(e.target.value)}
                                            placeholder="Commission"
                                            className="w-24 border border-admin-border-strong rounded-admin-button px-1.5 py-1.5 text-xs"
                                        />
                                        <AdminButton
                                            type="button"
                                            variant="neutral"
                                            isLoading={addingCustomFixedFault}
                                            onClick={addNewDiscoveredFixedFault}
                                            disabled={customFixedFault.trim() === ''}
                                        >
                                            Add
                                        </AdminButton>
                                    </div>
                                </div>
                                <label className="text-xs font-semibold text-admin-text">Resolution notes (optional)</label>
                                <textarea
                                    value={resolutionNotes}
                                    onChange={(e) => setResolutionNotes(e.target.value)}
                                    rows={2}
                                    placeholder="What was actually done — especially useful if no parts were used"
                                    className="border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs"
                                />
                                <AdminButton type="submit" isLoading={processing}>
                                    Finish work — send to quality check
                                </AdminButton>
                            </form>

                            <form onSubmit={failRepair} className="flex flex-col gap-2 border-t border-admin-border pt-3 mt-1">
                                <label className="text-xs font-semibold text-admin-text">Mark as failed — reason</label>
                                <textarea
                                    value={failReason}
                                    onChange={(e) => setFailReason(e.target.value)}
                                    rows={2}
                                    className="border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs"
                                />
                                {repair.down_payment_paid_minor > 0 && (
                                    <div className="flex flex-col gap-1">
                                        <label className="text-xs font-semibold text-admin-text">Service charge to deduct from down payment</label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            max={repair.down_payment_paid_minor / 100}
                                            value={serviceCharge}
                                            onChange={(e) => setServiceCharge(e.target.value)}
                                            placeholder="0"
                                            className="border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs"
                                        />
                                        <span className="text-xs text-admin-text2">
                                            Paid down payment: {formatNaira(repair.down_payment_paid_minor)}. Refund due:{' '}
                                            {formatNaira(Math.max(0, repair.down_payment_paid_minor - Math.round(parseFloat(serviceCharge || '0') * 100)))}
                                        </span>
                                    </div>
                                )}
                                <AdminButton type="submit" variant="warning" isLoading={processing} disabled={failReason.trim() === ''}>
                                    Repair failed
                                </AdminButton>
                            </form>
                        </>
                    )}

                    {repair.repair_status === 'qc_pending' && (
                        <form onSubmit={recordQualityCheck} className="flex flex-col gap-2">
                            <span className="text-xs font-semibold text-admin-text">Quality check</span>
                            <div className="flex flex-col gap-1.5 text-xs text-admin-text">
                                <label className="flex items-center gap-2">
                                    <input type="radio" name="qc" checked={qcOutcome === 'passed'} onChange={() => setQcOutcome('passed')} />
                                    Passed — works as it should
                                </label>
                                <label className="flex items-center gap-2">
                                    <input type="radio" name="qc" checked={qcOutcome === 'not_testable'} onChange={() => setQcOutcome('not_testable')} />
                                    Not testable — could not fully test (note why)
                                </label>
                                <label className="flex items-center gap-2">
                                    <input type="radio" name="qc" checked={qcOutcome === 'failed'} onChange={() => setQcOutcome('failed')} />
                                    Failed — still faulty (note what)
                                </label>
                            </div>
                            <textarea
                                value={qcNotes}
                                onChange={(e) => setQcNotes(e.target.value)}
                                rows={2}
                                required={qcOutcome !== 'passed'}
                                placeholder={qcOutcome === 'passed' ? 'Optional' : 'Required for a failed or untestable check'}
                                className="border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs"
                            />
                            <AdminButton type="submit" isLoading={processing}>
                                Record quality check
                            </AdminButton>
                        </form>
                    )}

                    {canShowRepairTransfer && (
                        <form onSubmit={transferRepair} className="flex flex-col gap-2 border-t border-admin-border pt-3">
                            <label className="text-xs font-semibold text-admin-text">Transfer repair work</label>
                            {transferTargetShops.length === 0 ? (
                                <span className="text-xs text-admin-text2">No other active shop is available for transfer.</span>
                            ) : (
                                <>
                                    <select
                                        value={transferShopId}
                                        onChange={(e) => setTransferShopId(e.target.value)}
                                        className="border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs"
                                    >
                                        <option value="">Select receiving shop...</option>
                                        {transferTargetShops.map((availableShop) => (
                                            <option key={availableShop.id} value={availableShop.id}>
                                                {availableShop.name}
                                            </option>
                                        ))}
                                    </select>
                                    <textarea
                                        value={transferReason}
                                        onChange={(e) => setTransferReason(e.target.value)}
                                        rows={2}
                                        placeholder="Reason (optional)"
                                        className="border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs"
                                    />
                                    {reservedPartsCount > 0 && (
                                        <label className="flex items-start gap-2 text-xs text-admin-text2">
                                            <input
                                                type="checkbox"
                                                checked={releaseReservedParts}
                                                onChange={(e) => setReleaseReservedParts(e.target.checked)}
                                                className="mt-0.5"
                                            />
                                            <span>
                                                Release {reservedPartsCount} reserved part{reservedPartsCount === 1 ? '' : 's'} back to this shop. Transfer stock separately if the receiving shop also needs those parts.
                                            </span>
                                        </label>
                                    )}
                                    <AdminButton
                                        type="submit"
                                        variant="neutral"
                                        isLoading={processing}
                                        disabled={!transferShopId || (reservedPartsCount > 0 && !releaseReservedParts)}
                                    >
                                        Transfer repair
                                    </AdminButton>
                                </>
                            )}
                        </form>
                    )}

                    {['awaiting_authorization', 'payment_overdue', 'awaiting_parts', 'in_progress', 'qc_pending', 'failed_requires_resolution'].includes(
                        repair.repair_status,
                    ) && (
                        <div className="flex flex-col gap-2 border-t border-admin-border pt-3">
                            <label className="text-xs font-semibold text-admin-text">Cancel repair — reason</label>
                            <textarea
                                value={cancelReason}
                                onChange={(e) => setCancelReason(e.target.value)}
                                rows={2}
                                placeholder="e.g. customer declined the quote"
                                className="border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs"
                            />
                            <AdminButton variant="danger" onClick={cancelRepair} disabled={processing || cancelReason.trim() === ''}>
                                Cancel repair
                            </AdminButton>
                        </div>
                    )}


                    {repair.repair_status === 'handover' && repair.financial_status === 'fully_paid' && (
                        <div className="flex flex-col gap-2">
                            <div className="bg-admin-green-soft text-admin-green-text rounded-admin-button px-2 py-2 text-xs">
                                Full payment is approved. Hand the device over to the customer, then release it from the collection case
                                so the repair is marked completed.
                            </div>
                            {repair.collection_case && (
                                <AdminButton onClick={() => router.visit(`/admin/collection/${repair.collection_case!.id}`)}>
                                    Confirm handover on collection case
                                </AdminButton>
                            )}
                        </div>
                    )}

                    {repair.repair_status === 'handover' && repair.financial_status !== 'fully_paid' && (
                        <>
                            {repair.pending_payment_approval ? (
                                <div className="flex flex-col gap-2">
                                    <div className="bg-ui-warning-soft text-ui-warning-text rounded-admin-button px-2 py-2 text-xs">
                                        Payment recording pending Shop Owner approval —{' '}
                                        {repair.pending_payment_approval.requested_financial_status.replace(/_/g, ' ')} (
                                        {formatNaira(repair.pending_payment_approval.amount_minor)} via{' '}
                                        {repair.pending_payment_approval.method.replace(/_/g, ' ')})
                                    </div>
                                    {canApprovePayment && (
                                        <div className="flex items-center gap-2">
                                            <AdminButton
                                                onClick={() => decidePaymentApproval(repair.pending_payment_approval!.id, true)}
                                                isLoading={processing}
                                            >
                                                Approve
                                            </AdminButton>
                                            <AdminButton
                                                variant="neutral"
                                                onClick={() => decidePaymentApproval(repair.pending_payment_approval!.id, false)}
                                                disabled={processing}
                                            >
                                                Reject
                                            </AdminButton>
                                        </div>
                                    )}
                                </div>
                            ) : (
                                <form onSubmit={requestPaymentApproval} className="flex flex-col gap-2">
                                    <label className="text-xs font-semibold text-admin-text">Request payment recording</label>
                                    {repair.outstanding_balance_minor !== null && (
                                        <span className="text-xs text-admin-text2">
                                            Outstanding balance: {formatNaira(repair.outstanding_balance_minor)}
                                        </span>
                                    )}
                                    <select
                                        value={requestedFinancialStatus}
                                        onChange={(e) => setRequestedFinancialStatus(e.target.value)}
                                        className="border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs"
                                    >
                                        <option value="fully_paid">Fully paid</option>
                                        <option value="partially_paid">Partially paid</option>
                                    </select>
                                    <input
                                        type="number"
                                        step="0.01"
                                        placeholder="Amount (₦)"
                                        value={requestedAmount}
                                        onChange={(e) => setRequestedAmount(e.target.value)}
                                        className="border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs"
                                    />
                                    <select
                                        value={requestedPaymentMethod}
                                        onChange={(e) => setRequestedPaymentMethod(e.target.value)}
                                        className="border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs"
                                    >
                                        <option value="cash">Cash</option>
                                        <option value="pos_terminal">POS terminal</option>
                                        <option value="bank_transfer">Bank transfer</option>
                                    </select>
                                    <AdminButton type="submit" isLoading={processing}>
                                        Request approval
                                    </AdminButton>
                                </form>
                            )}
                        </>
                    )}

                    {repair.repair_status === 'failed_requires_resolution' && (
                        <>
                            <AdminButton onClick={resume} disabled={processing}>
                                Resume repair
                            </AdminButton>

                            <form onSubmit={markUnrepairable} className="flex flex-col gap-2 border-t border-admin-border pt-3 mt-1">
                                <label className="text-xs font-semibold text-admin-text">Mark unrepairable — settlement</label>
                                <select
                                    value={settlementState}
                                    onChange={(e) => setSettlementState(e.target.value)}
                                    className="border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs"
                                >
                                    <option value="pending_decision">Pending decision</option>
                                    <option value="refunded">Refunded</option>
                                    <option value="retained">Retained</option>
                                </select>
                                <AdminButton type="submit" variant="neutral" isLoading={processing}>
                                    Mark unrepairable
                                </AdminButton>
                            </form>
                        </>
                    )}

                    {CLOSED_STATUSES.includes(repair.repair_status) && !(repair.repair_status === 'completed' && repair.financial_status !== 'fully_paid') && (
                        <p className="text-xs text-admin-text3">
                            {repair.collection_case ? 'See the linked collection case for next steps.' : 'No further actions available.'}
                        </p>
                    )}
                </div>
            </div>

            <div className="pt-6 max-w-4xl">
                <AdminSectionCard title="Photos">
                    <div className="p-6">
                        <RepairPhotoGallery repairId={repair.id} photos={repair.photos} disabled={CLOSED_STATUSES.includes(repair.repair_status)} />
                    </div>
                </AdminSectionCard>
            </div>
            </div>
        </AdminShell>
    );
}
