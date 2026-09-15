import { Head, router } from '@inertiajs/react';
import AdminShell from '../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../Components/Admin/AdminPageHead';
import AdminButton from '../../../Components/Admin/AdminButton';
import AdminEmptyState from '../../../Components/Admin/AdminEmptyState';

type RepairDetail = {
    id: number;
    device_make: string;
    device_model: string;
    collection_case: { id: number; status: string; context: string; collection_deadline_at: string } | null;
};

interface RepairCollectionProps {
    repair: RepairDetail;
}

/** Read-only view into the linked collection_cases row — full detail/actions live on Admin/Collection/Show (Implementation Plan Phase 6 UI list). */
export default function RepairCollection({ repair }: RepairCollectionProps) {
    return (
        <AdminShell>
            <Head title={`Collection — Repair #${repair.id}`} />

            <AdminPageHead
                title={`Collection — ${repair.device_make} ${repair.device_model}`}
                description={`Repair #${repair.id}`}
                actions={
                    <AdminButton variant="neutral" onClick={() => router.visit(`/admin/repairs/${repair.id}`)}>
                        Back to repair
                    </AdminButton>
                }
            />

            {repair.collection_case ? (
                <div className="max-w-md bg-admin-surface border border-admin-border rounded-admin-card p-6">
                    <p className="text-sm text-admin-text2 mb-1">Status</p>
                    <p className="text-sm font-semibold text-admin-text mb-4 capitalize">{repair.collection_case.status.replace(/_/g, ' ')}</p>
                    <p className="text-sm text-admin-text2 mb-1">Collection deadline</p>
                    <p className="text-sm font-semibold text-admin-text mb-4">
                        {new Date(repair.collection_case.collection_deadline_at).toLocaleString()}
                    </p>
                    <AdminButton onClick={() => router.visit(`/admin/collection/${repair.collection_case!.id}`)}>
                        Open collection case
                    </AdminButton>
                </div>
            ) : (
                <AdminEmptyState icon="box-seam" title="No collection case yet" description="One is created automatically once this repair completes or is marked unrepairable." />
            )}
        </AdminShell>
    );
}
