import { Head, router } from '@inertiajs/react';
import AdminShell from '../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../Components/Admin/AdminPageHead';
import AdminButton from '../../../Components/Admin/AdminButton';
import DiagnosisChecklist, { DiagnosisRow } from '../../../Features/Repairs/DiagnosisChecklist';

type RepairDetail = {
    id: number;
    device_make: string;
    device_model: string;
    repair_status: string;
    diagnoses: DiagnosisRow[];
};

interface RepairDiagnosisProps {
    repair: RepairDetail;
}

export default function RepairDiagnosis({ repair }: RepairDiagnosisProps) {
    const finalized = repair.repair_status !== 'received' && repair.repair_status !== 'diagnosing';

    return (
        <AdminShell>
            <Head title={`Diagnosis — Repair #${repair.id}`} />

            <AdminPageHead
                title={`Diagnosis — ${repair.device_make} ${repair.device_model}`}
                description={`Repair #${repair.id}`}
                actions={
                    <AdminButton variant="neutral" onClick={() => router.visit(`/admin/repairs/${repair.id}`)}>
                        Back to repair
                    </AdminButton>
                }
            />

            <div className="max-w-2xl bg-admin-surface border border-admin-border rounded-admin-card p-6">
                <DiagnosisChecklist
                    submitUrl={`/admin/repairs/${repair.id}/diagnosis`}
                    diagnoses={repair.diagnoses}
                    disabled={finalized}
                />
                {finalized && (
                    <p className="text-xs text-admin-text3 mt-4 pt-4 border-t border-admin-border">
                        Diagnosis is finalized for this repair — no further observations can be added.
                    </p>
                )}
            </div>
        </AdminShell>
    );
}
