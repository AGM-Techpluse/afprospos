import { FormEvent, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { useConfirm } from '../../../hooks/useConfirm';
import AdminShell from '../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../Components/Admin/AdminPageHead';
import AdminButton from '../../../Components/Admin/AdminButton';
import RepairStatusStepper from '../../../Features/Repairs/RepairStatusStepper';
import { formatNaira } from '../../../lib/money';

type RepairDetail = {
    id: number;
    shop_id: number;
    customer_id: number;
    device_make: string;
    device_model: string;
    technician_staff_id: number | null;
    labour_charge_minor: number;
    down_payment_required_minor: number | null;
    down_payment_deadline_at: string | null;
    repair_status: string;
    financial_status: string;
    unrepairable_settlement_state: string | null;
    created_at: string;
    collection_case: { id: number; status: string; context: string; collection_deadline_at: string } | null;
};

type Technician = { id: number; name: string };

interface RepairShowProps {
    repair: RepairDetail;
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

export default function RepairShow({ repair, technicians }: RepairShowProps) {
    const confirm = useConfirm();
    const [processing, setProcessing] = useState(false);
    const [downPayment, setDownPayment] = useState('');
    const [downPaymentMethod, setDownPaymentMethod] = useState('cash');
    const [financialStatus, setFinancialStatus] = useState('fully_paid');
    const [failReason, setFailReason] = useState('');
    const [settlementState, setSettlementState] = useState('pending_decision');
    const [technicianId, setTechnicianId] = useState(repair.technician_staff_id?.toString() ?? '');

    function post(url: string, data: Record<string, string> = {}) {
        setProcessing(true);
        router.post(url, data, { onFinish: () => setProcessing(false) });
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
        post(`/admin/repairs/${repair.id}/complete`, { financial_status: financialStatus });
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
                description={`Repair #${repair.id} — Customer #${repair.customer_id}`}
            />

            <div className="mb-6">
                <RepairStatusStepper status={repair.repair_status} />
            </div>

            <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px] items-start max-w-4xl">
                <div className="bg-admin-surface border border-admin-border rounded-admin-card p-6">
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
                        value={repair.technician_staff_id ? (assignedTechnicianName ?? `Staff #${repair.technician_staff_id}`) : 'Unassigned'}
                    />
                    <DetailRow label="Received" value={new Date(repair.created_at).toLocaleString()} />

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
        </AdminShell>
    );
}
