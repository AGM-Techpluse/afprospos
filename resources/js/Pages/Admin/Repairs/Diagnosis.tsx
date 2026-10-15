import { Head, router } from '@inertiajs/react';
import AdminShell from '../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../Components/Admin/AdminPageHead';
import AdminButton from '../../../Components/Admin/AdminButton';
import DiagnosisChecklist, { DiagnosisRow } from '../../../Features/Repairs/DiagnosisChecklist';
import CommonComponentsCard from '../../../Features/Repairs/CommonComponentsCard';
import DiagnosisConclusionForm, { DiagnosisConclusionRow } from '../../../Features/Repairs/DiagnosisConclusionForm';

type RepairDetail = {
    id: number;
    device_make: string;
    device_model: string;
    repair_status: string;
    repair_cost_minor: number | null;
    diagnoses: DiagnosisRow[];
    diagnosis_conclusions: DiagnosisConclusionRow[];
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

            <div className="grid lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)] gap-4 items-start mb-6">
                <div className="flex flex-col gap-4 min-w-0">
                    <section className="bg-admin-surface border border-admin-border rounded-admin-card p-6">
                        <h3 className="text-sm font-semibold text-admin-text mb-1">1. Test common components</h3>
                        <p className="text-xs text-admin-text3 mb-4">Record what is faulty before diagnosing the fault.</p>
                        <DiagnosisChecklist
                            submitUrl={`/admin/repairs/${repair.id}/diagnosis`}
                            diagnoses={repair.diagnoses}
                            disabled={finalized}
                        />
                    </section>

                    <section className="bg-admin-surface border border-admin-border rounded-admin-card p-6">
                        <h3 className="text-sm font-semibold text-admin-text mb-1">2. Diagnosis</h3>
                        <DiagnosisConclusionForm
                            submitUrl={`/admin/repairs/${repair.id}/diagnosis-conclusion`}
                            conclusions={repair.diagnosis_conclusions}
                            disabled={finalized}
                            bare
                        />
                        {finalized && (
                            <p className="text-xs text-admin-text3 mt-4">
                                Diagnosis is finalized for this repair — no further observations can be added.
                            </p>
                        )}
                    </section>
                </div>

                {/* This page predates the Diagnosis Cases flow (see DiagnosisCases/Create.tsx) and isn't reachable from the live UI anymore — components={[]} keeps it compiling without wiring the catalog into a page nothing links to. */}
                <CommonComponentsCard
                    components={[]}
                    submitUrl={`/admin/repairs/${repair.id}/diagnosis`}
                    diagnosedComponents={repair.diagnoses.map((d) => d.component)}
                    disabled={finalized}
                />
            </div>

        </AdminShell>
    );
}
