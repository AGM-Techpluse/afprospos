import { Head, router } from '@inertiajs/react';
import AdminShell from '../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../Components/Admin/AdminPageHead';
import AdminButton from '../../../Components/Admin/AdminButton';
import PartsReservationPicker, { PartReservationRow } from '../../../Features/Repairs/PartsReservationPicker';

type RepairDetail = {
    id: number;
    device_make: string;
    device_model: string;
    repair_status: string;
    part_reservations: PartReservationRow[];
};

interface RepairPartsProps {
    repair: RepairDetail;
}

export default function RepairParts({ repair }: RepairPartsProps) {
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
                <p className="text-xs text-admin-text3 mb-4">
                    Parts can only be reserved once this repair is authorized (awaiting parts or in progress).
                </p>
            )}

            <PartsReservationPicker repairId={repair.id} reservations={repair.part_reservations} disabled={!reservable} />
        </AdminShell>
    );
}
