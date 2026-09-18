import { FormEvent, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { useConfirm } from '../../../hooks/useConfirm';
import AdminShell from '../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../Components/Admin/AdminPageHead';
import AdminButton from '../../../Components/Admin/AdminButton';
import RepairStatusStepper from '../../../Features/Repairs/RepairStatusStepper';
import DeviceLockInput, { DeviceLockType } from '../../../Features/Repairs/DeviceLockInput';
import PatternLockPad from '../../../Features/Repairs/PatternLockPad';
import RepairPhotoGallery, { RepairPhoto } from '../../../Features/Repairs/RepairPhotoGallery';
import { formatNaira } from '../../../lib/money';

type RepairDetail = {
    id: number;
    shop_id: number;
    customer_id: number;
    customer_name: string | null;
    customer_phone: string | null;
    customer_email: string | null;
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
    labour_charge_minor: number;
    down_payment_required_minor: number | null;
    down_payment_deadline_at: string | null;
    repair_status: string;
    financial_status: string;
    unrepairable_settlement_state: string | null;
    resolution_notes: string | null;
    created_at: string;
    collection_case: { id: number; status: string; context: string; collection_deadline_at: string } | null;
};

type Technician = { id: number; name: string };
type Shop = { id: number; name: string };

interface RepairShowProps {
    repair: RepairDetail;
    shop: Shop | null;
    technicians: Technician[];
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

export default function RepairShow({ repair, shop, technicians }: RepairShowProps) {
    const confirm = useConfirm();
    const [processing, setProcessing] = useState(false);
    const [downPayment, setDownPayment] = useState('');
    const [downPaymentMethod, setDownPaymentMethod] = useState('cash');
    const [financialStatus, setFinancialStatus] = useState('fully_paid');
    const [resolutionNotes, setResolutionNotes] = useState('');
    const [failReason, setFailReason] = useState('');
    const [settlementState, setSettlementState] = useState('pending_decision');
    const [technicianId, setTechnicianId] = useState(repair.technician_staff_id?.toString() ?? '');
    const [editingLock, setEditingLock] = useState(false);
    const [lockType, setLockType] = useState<DeviceLockType>(repair.device_lock_type);
    const [lockValue, setLockValue] = useState(repair.device_lock_value ?? '');

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

    const assignedTechnicianName = technicians.find((t) => t.id === repair.technician_staff_id)?.name;

    function authorize(e: FormEvent) {
        e.preventDefault();
        post(`/admin/repairs/${repair.id}/authorize`, {
            down_payment_required_minor: String(Math.round(parseFloat(downPayment || '0') * 100)),
            down_payment_method: downPaymentMethod,
        });
    }

    function completeRepair(e: FormEvent) {
        e.preventDefault();
        post(`/admin/repairs/${repair.id}/complete`, { financial_status: financialStatus, resolution_notes: resolutionNotes });
    }

    async function failRepair(e: FormEvent) {
        e.preventDefault();
        post(`/admin/repairs/${repair.id}/fail`, { reason: failReason });
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
            <Head title={`Repair #${repair.id}`} />

            <AdminPageHead
                title={`${repair.device_make} ${repair.device_model}`}
                description={`Repair #${repair.id} — ${repair.customer_name ?? 'Customer'}`}
            />

            <div className="mb-6">
                <RepairStatusStepper status={repair.repair_status} />
            </div>

            <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px] items-start max-w-4xl">
                <div className="bg-admin-surface border border-admin-border rounded-admin-card p-6">
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
                        <p className="text-sm font-medium text-admin-text">{repair.customer_name ?? 'Unknown customer'}</p>
                        {repair.customer_phone && <p className="text-sm text-admin-text2">{repair.customer_phone}</p>}
                        {repair.customer_email && <p className="text-sm text-admin-text2">{repair.customer_email}</p>}
                    </div>

                    <DetailRow label="Device" value={`${repair.device_make} ${repair.device_model}`} />
                    <DetailRow label="Shop" value={shop ? shop.name : 'Unknown shop'} />
                    <DetailRow label="IMEI / serial" value={repair.device_imei_serial ?? 'Not recorded'} />

                    <div className="py-2.5 border-b border-admin-border">
                        <div className="flex items-center justify-between mb-1.5">
                            <span className="text-sm text-admin-text2">Device lock</span>
                            {!editingLock && (
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

                    <DetailRow label="Labour charge" value={formatNaira(repair.labour_charge_minor)} />
                    <DetailRow
                        label="Down payment required"
                        value={repair.down_payment_required_minor !== null ? formatNaira(repair.down_payment_required_minor) : '—'}
                    />
                    <DetailRow
                        label="Authorization deadline"
                        value={repair.down_payment_deadline_at ? new Date(repair.down_payment_deadline_at).toLocaleString() : '—'}
                    />
                    <DetailRow label="Financial status" value={repair.financial_status.replace(/_/g, ' ')} />
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

                    <div className="mt-4 pt-4 border-t border-admin-border flex gap-2">
                        <AdminButton variant="neutral" onClick={() => router.visit(`/admin/repairs/${repair.id}/diagnosis`)}>
                            Diagnosis
                        </AdminButton>
                        <AdminButton variant="neutral" onClick={() => router.visit(`/admin/repairs/${repair.id}/parts`)}>
                            Parts
                        </AdminButton>
                    </div>
                </div>

                <div className="bg-admin-surface border border-admin-border rounded-admin-card p-6 flex flex-col gap-2">
                    <h2 className="text-sm font-semibold text-admin-text mb-1">Actions</h2>

                    {!CLOSED_STATUSES.includes(repair.repair_status) && (
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

                    {(repair.repair_status === 'received' || repair.repair_status === 'diagnosing') && (
                        <>
                            <p className="text-xs text-admin-text3">
                                Record a diagnosis to move this job forward — authorizing repair work, or closing it out as
                                unrepairable, both happen from the Diagnosis page.
                            </p>
                            <AdminButton onClick={() => router.visit(`/admin/repairs/${repair.id}/diagnosis`)}>Go to diagnosis</AdminButton>
                        </>
                    )}

                    {(repair.repair_status === 'awaiting_authorization' || repair.repair_status === 'payment_overdue') && (
                        <form onSubmit={authorize} className="flex flex-col gap-2">
                            <label className="text-xs font-semibold text-admin-text">Down payment (₦, 0 if none)</label>
                            <input
                                type="number"
                                step="0.01"
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
                            <AdminButton type="submit" isLoading={processing}>
                                Set authorization
                            </AdminButton>
                            <p className="text-xs text-admin-text3">
                                Once the corresponding payment shows confirmed under Payments, click below to move this job forward.
                            </p>
                            <AdminButton
                                type="button"
                                variant="neutral"
                                onClick={() => post(`/admin/repairs/${repair.id}/confirm-down-payment`)}
                                disabled={processing}
                            >
                                Down payment confirmed
                            </AdminButton>
                        </form>
                    )}

                    {repair.repair_status === 'awaiting_parts' && (
                        <AdminButton onClick={() => post(`/admin/repairs/${repair.id}/start`)} isLoading={processing}>
                            Start repair
                        </AdminButton>
                    )}

                    {repair.repair_status === 'in_progress' && (
                        <>
                            <form onSubmit={completeRepair} className="flex flex-col gap-2">
                                <label className="text-xs font-semibold text-admin-text">Complete repair — financial status</label>
                                <select
                                    value={financialStatus}
                                    onChange={(e) => setFinancialStatus(e.target.value)}
                                    className="border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs"
                                >
                                    <option value="fully_paid">Fully paid</option>
                                    <option value="partially_paid">Partially paid</option>
                                    <option value="unpaid">Unpaid</option>
                                </select>
                                <label className="text-xs font-semibold text-admin-text">Resolution notes (optional)</label>
                                <textarea
                                    value={resolutionNotes}
                                    onChange={(e) => setResolutionNotes(e.target.value)}
                                    rows={2}
                                    placeholder="What was actually done — especially useful if no parts were used"
                                    className="border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs"
                                />
                                <AdminButton type="submit" isLoading={processing}>
                                    Complete repair
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
                                <AdminButton type="submit" variant="neutral" isLoading={processing}>
                                    Repair failed
                                </AdminButton>
                            </form>
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

                    {CLOSED_STATUSES.includes(repair.repair_status) && (
                        <p className="text-xs text-admin-text3">
                            {repair.collection_case ? 'See the linked collection case for next steps.' : 'No further actions available.'}
                        </p>
                    )}
                </div>
            </div>

            <div className="bg-admin-surface border border-admin-border rounded-admin-card p-6 max-w-4xl mt-6">
                <RepairPhotoGallery repairId={repair.id} photos={repair.photos} disabled={CLOSED_STATUSES.includes(repair.repair_status)} />
            </div>
        </AdminShell>
    );
}
