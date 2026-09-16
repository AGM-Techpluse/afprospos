import { Head, router } from '@inertiajs/react';
import AdminShell from '../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../Components/Admin/AdminPageHead';
import AdminButton from '../../../Components/Admin/AdminButton';
import AlertBanner from '../../../Components/Feedback/AlertBanner';
import PartsReservationPicker, { PartReservationRow, SuggestedPart } from '../../../Features/Repairs/PartsReservationPicker';

type RepairDetail = {
    id: number;
    device_make: string;
    device_model: string;
    repair_status: string;
    part_reservations: PartReservationRow[];
};

interface RepairPartsProps {
    repair: RepairDetail;
    suggestedParts: SuggestedPart[];
}

const STATUS_LABEL: Record<string, string> = {
    received: 'Received',
    diagnosing: 'Diagnosing',
    awaiting_authorization: 'Awaiting authorization',
    payment_overdue: 'Payment overdue',
    expired_cancelled: 'Expired / cancelled',
    awaiting_parts: 'Awaiting parts',
    in_progress: 'In progress',
    completed: 'Completed',
    unrepairable: 'Unrepairable',
    failed_requires_resolution: 'Failed — requires resolution',
};

export default function RepairParts({ repair, suggestedParts }: RepairPartsProps) {
    const reservable = repair.repair_status === 'awaiting_parts' || repair.repair_status === 'in_progress';

    return (
        <AdminShell>
            <Head title={`Parts — Repair #${repair.id}`} />

            <AdminPageHead
                title={`Parts — ${repair.device_make} ${repair.device_model}`}
                description={`Repair #${repair.id}`}
                actions={
                    <AdminButton variant="neutral" onClick={() => router.visit(`/admin/repairs/${repair.id}`)}>
                        Back to repair
                    </AdminButton>
                }
            />

            {!reservable && (
                <div className="mb-6">
                    <AlertBanner kind="danger">
                        This repair is currently <strong>{STATUS_LABEL[repair.repair_status] ?? repair.repair_status}</strong> — parts search
                        and reservation unlock once the repair is authorized and reaches Awaiting parts or In progress. Nothing here is broken;
                        finish diagnosis and authorization on the repair page first.
                    </AlertBanner>
                </div>
            )}

            <PartsReservationPicker
                repairId={repair.id}
                reservations={repair.part_reservations}
                suggestedParts={suggestedParts}
                disabled={!reservable}
            />
        </AdminShell>
    );
}
