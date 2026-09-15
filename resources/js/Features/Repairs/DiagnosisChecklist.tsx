import { FormEvent, useState } from 'react';
import { useForm } from '@inertiajs/react';
import AdminButton from '../../Components/Admin/AdminButton';
import AdminEmptyState from '../../Components/Admin/AdminEmptyState';

export type DiagnosisRow = {
    id: number;
    component: string;
    condition: string;
    notes: string | null;
    outcome: string | null;
    diagnosed_by_staff_id: number;
    created_at: string;
};

const CONDITIONS = [
    { value: 'working', label: 'Working' },
    { value: 'faulty', label: 'Faulty' },
    { value: 'not_tested', label: 'Not tested' },
    { value: 'unable_to_test', label: 'Unable to test' },
];

const OUTCOMES = [
    { value: 'repairable', label: 'Repairable' },
    { value: 'unrepairable', label: 'Unrepairable' },
    { value: 'requires_further_assessment', label: 'Requires further assessment' },
];

interface DiagnosisChecklistProps {
    submitUrl: string;
    diagnoses: DiagnosisRow[];
    disabled?: boolean;
}

/** Per-component condition entry + finalize-outcome action (Implementation Plan Phase 6 UI list). Passing an outcome both records the observation and finalizes the diagnosis in the same call. */
export default function DiagnosisChecklist({ submitUrl, diagnoses, disabled = false }: DiagnosisChecklistProps) {
    const [finalizing, setFinalizing] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        component: '',
        condition: 'faulty',
        notes: '',
        outcome: '',
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        post(submitUrl, {
            preserveScroll: true,
            onSuccess: () => reset('component', 'notes', 'outcome'),
        });
    }

    return (
        <div>
            <div className="mb-4">
                {diagnoses.length === 0 ? (
                    <AdminEmptyState icon="clipboard2-pulse" title="No observations yet" description="Record each component you assess below." />
                ) : (
                    <ul className="border border-admin-border rounded-admin-card divide-y divide-admin-border">
                        {diagnoses.map((diagnosis) => (
                            <li key={diagnosis.id} className="p-3">
                                <div className="flex items-center justify-between gap-2">
                                    <span className="text-sm font-medium text-admin-text capitalize">{diagnosis.component.replace('_', ' ')}</span>
                                    <span className="text-xs font-semibold text-admin-text2 capitalize">{diagnosis.condition.replace('_', ' ')}</span>
                                </div>
                                {diagnosis.notes && <p className="text-xs text-admin-text3 mt-1">{diagnosis.notes}</p>}
                                {diagnosis.outcome && (
                                    <p className="text-xs font-semibold text-admin-blue mt-1">
                                        Finalized: {OUTCOMES.find((o) => o.value === diagnosis.outcome)?.label ?? diagnosis.outcome}
                                    </p>
                                )}
                            </li>
                        ))}
                    </ul>
                )}
            </div>

            {!disabled && (
                <form onSubmit={submit} className="border-t border-admin-border pt-4">
                    <div className="grid sm:grid-cols-2 gap-3 mb-3">
                        <div>
                            <label className="text-xs font-semibold text-admin-text mb-1 block">Component</label>
                            <input
                                type="text"
                                value={data.component}
                                onChange={(e) => setData('component', e.target.value)}
                                placeholder="e.g. screen, battery, camera"
                                className="w-full border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs"
                            />
                            {errors.component && <p className="text-xs text-admin-red mt-1">{errors.component}</p>}
                        </div>
                        <div>
                            <label className="text-xs font-semibold text-admin-text mb-1 block">Condition</label>
                            <select
                                value={data.condition}
                                onChange={(e) => setData('condition', e.target.value)}
                                className="w-full border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs"
                            >
                                {CONDITIONS.map((c) => (
                                    <option key={c.value} value={c.value}>
                                        {c.label}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </div>

                    <div className="mb-3">
                        <label className="text-xs font-semibold text-admin-text mb-1 block">Notes (optional)</label>
                        <textarea
                            value={data.notes}
                            onChange={(e) => setData('notes', e.target.value)}
                            rows={2}
                            className="w-full border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs"
                        />
                    </div>

                    {finalizing && (
                        <div className="mb-3">
                            <label className="text-xs font-semibold text-admin-text mb-1 block">Finalize diagnosis as</label>
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
                    )}

                    <div className="flex items-center gap-2">
                        <AdminButton type="submit" isLoading={processing} disabled={finalizing && data.outcome === ''}>
                            {finalizing ? 'Save & finalize' : 'Save observation'}
                        </AdminButton>
                        <AdminButton
                            type="button"
                            variant="neutral"
                            onClick={() => {
                                setFinalizing((current) => !current);
                                setData('outcome', '');
                            }}
                        >
                            {finalizing ? 'Cancel finalize' : 'Finalize diagnosis'}
                        </AdminButton>
                    </div>
                </form>
            )}
        </div>
    );
}
