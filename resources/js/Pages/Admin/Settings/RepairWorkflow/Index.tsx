import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminShell from '../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../Components/Admin/AdminPageHead';
import SettingsSubNav from '../../../../Components/Admin/SettingsSubNav';
import AdminButton from '../../../../Components/Admin/AdminButton';

interface RepairWorkflowProps {
    qcFailAction: 'diagnosis' | 'cancel';
}

const OPTIONS = [
    {
        value: 'diagnosis',
        label: 'Send it back to diagnosis',
        description: 'The device returns to diagnosis so the fault can be found and fixed again. The repair stays open.',
    },
    {
        value: 'cancel',
        label: 'Cancel the repair',
        description: 'The repair is closed and reserved parts go back to stock. The customer must be told and a new repair opened.',
    },
] as const;

/** Settings → Repair workflow. Chooses what a failed quality check does. */
export default function RepairWorkflowIndex({ qcFailAction }: RepairWorkflowProps) {
    const [action, setAction] = useState<'diagnosis' | 'cancel'>(qcFailAction);
    const [busy, setBusy] = useState(false);

    function save() {
        setBusy(true);
        router.put('/admin/settings/repair-workflow', { action }, { preserveScroll: true, onFinish: () => setBusy(false) });
    }

    return (
        <AdminShell>
            <Head title="Repair workflow" />

            <AdminPageHead title="Repair workflow" description="How the shop handles a repair that fails its quality check." />

            <SettingsSubNav />

            <div className="max-w-2xl">
                <h2 className="text-sm font-semibold text-admin-text mb-3">When a quality check fails</h2>
                <div className="flex flex-col gap-3">
                    {OPTIONS.map((option) => (
                        <label
                            key={option.value}
                            className={`flex items-start gap-3 rounded-admin-card border p-4 cursor-pointer ${
                                action === option.value ? 'border-admin-blue bg-admin-blue-soft' : 'border-admin-border bg-admin-surface'
                            }`}
                        >
                            <input
                                type="radio"
                                name="qc-fail-action"
                                className="mt-1"
                                checked={action === option.value}
                                onChange={() => setAction(option.value)}
                            />
                            <span>
                                <span className="block text-sm font-medium text-admin-text">{option.label}</span>
                                <span className="block text-xs text-admin-text2 mt-0.5">{option.description}</span>
                            </span>
                        </label>
                    ))}
                </div>

                <p className="text-xs text-admin-text2 mt-4">
                    A quality check can also be marked “not testable” with a note. That completes the repair, so the device can be handed over.
                </p>

                <div className="mt-6">
                    <AdminButton type="button" onClick={save} disabled={busy || action === qcFailAction}>
                        {busy ? 'Saving…' : 'Save'}
                    </AdminButton>
                </div>
            </div>
        </AdminShell>
    );
}
