import { FormEvent } from 'react';
import { useForm } from '@inertiajs/react';
import AdminButton from '../../Components/Admin/AdminButton';

export type DiagnosisConclusionRow = {
    id: number;
    root_cause: string;
    outcome: string;
    diagnosed_by_staff_id: number;
    created_at: string;
};

const OUTCOMES = [
    { value: 'repairable', label: 'Repairable' },
    { value: 'unrepairable', label: 'Unrepairable' },
    { value: 'requires_further_assessment', label: 'Requires further assessment' },
];

interface DiagnosisConclusionFormProps {
    /** Rendered inside a parent card, so drop this form's own frame. */
    bare?: boolean;
    submitUrl: string;
    conclusions: DiagnosisConclusionRow[];
    disabled?: boolean;
}

/** Step 2 — the actual diagnosis (REP-BR-29): the technician's stated root cause of the fault plus a repairable/unrepairable/requires-further-assessment verdict, distinct from the component tests in step 1. */
export default function DiagnosisConclusionForm({ submitUrl, conclusions, disabled = false, bare = false }: DiagnosisConclusionFormProps) {
    const { data, setData, post, processing, errors } = useForm({
        root_cause: '',
        outcome: '',
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        post(submitUrl, { preserveScroll: true });
    }

    return (
        <div className={bare ? '' : 'bg-admin-surface border border-admin-border rounded-admin-card p-6'}>
            <p className="text-xs text-admin-text3 mb-4">
                Based on the component tests above, state the main cause of the fault and whether the device can be repaired.
            </p>

            {conclusions.length > 0 && (
                <ul className="border border-admin-border rounded-admin-card divide-y divide-admin-border mb-4">
                    {conclusions.map((conclusion) => (
                        <li key={conclusion.id} className="p-3">
                            <p className="text-sm text-admin-text">{conclusion.root_cause}</p>
                            <p className="text-xs font-semibold text-admin-blue mt-1">
                                {OUTCOMES.find((o) => o.value === conclusion.outcome)?.label ?? conclusion.outcome}
                            </p>
                        </li>
                    ))}
                </ul>
            )}

            {!disabled && (
                <form onSubmit={submit}>
                    <div className="mb-3">
                        <label className="text-xs font-semibold text-admin-text mb-1 block">Main cause of the fault</label>
                        <textarea
                            value={data.root_cause}
                            onChange={(e) => setData('root_cause', e.target.value)}
                            rows={3}
                            placeholder="e.g. Battery has swollen and is pressing on the screen assembly"
                            className="w-full border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs"
                        />
                        {errors.root_cause && <p className="text-xs text-admin-red mt-1">{errors.root_cause}</p>}
                    </div>

                    <div className="mb-4">
                        <label className="text-xs font-semibold text-admin-text mb-1 block">Outcome</label>
                        <select
                            value={data.outcome}
                            onChange={(e) => setData('outcome', e.target.value)}
                            className="w-full border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs"
                        >
                            <option value="">— select —</option>
                            {OUTCOMES.map((o) => (
                                <option key={o.value} value={o.value}>
                                    {o.label}
                                </option>
                            ))}
                        </select>
                        {errors.outcome && <p className="text-xs text-admin-red mt-1">{errors.outcome}</p>}
                    </div>

                    <AdminButton type="submit" isLoading={processing} disabled={data.root_cause === '' || data.outcome === ''}>
                        Save diagnosis
                    </AdminButton>
                </form>
            )}
        </div>
    );
}
