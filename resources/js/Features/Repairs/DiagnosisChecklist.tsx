import { FormEvent } from 'react';
import { useForm } from '@inertiajs/react';
import AdminButton from '../../Components/Admin/AdminButton';
import AdminEmptyState from '../../Components/Admin/AdminEmptyState';

export type DiagnosisRow = {
    id: number;
    component: string;
    condition: string;
    notes: string | null;
    diagnosed_by_staff_id: number;
    created_at: string;
};

const CONDITIONS = [
    { value: 'working', label: 'Working' },
    { value: 'faulty', label: 'Faulty' },
    { value: 'not_tested', label: 'Not tested' },
    { value: 'unable_to_test', label: 'Unable to test' },
];

interface DiagnosisChecklistProps {
    submitUrl: string;
    diagnoses: DiagnosisRow[];
    disabled?: boolean;
}

/** Step 1 — test common components (REP-BR-29): per-component condition entry only. The technician's actual diagnosis (root cause + outcome) is a separate step — see DiagnosisConclusionForm. */
export default function DiagnosisChecklist({ submitUrl, diagnoses, disabled = false }: DiagnosisChecklistProps) {
    const { data, setData, post, processing, errors, reset } = useForm({
        component: '',
        condition: 'faulty',
        notes: '',
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        post(submitUrl, {
            preserveScroll: true,
            onSuccess: () => reset('component', 'notes'),
        });
    }

    return (
        <div>
            <div className="mb-4">
                {diagnoses.length === 0 ? (
                    <AdminEmptyState icon="clipboard2-pulse" title="No observations yet" description="Record each component you test below." />
                ) : (
                    <ul className="border border-admin-border rounded-admin-card divide-y divide-admin-border">
                        {diagnoses.map((diagnosis) => (
                            <li key={diagnosis.id} className="p-3">
                                <div className="flex items-center justify-between gap-2">
                                    <span className="text-sm font-medium text-admin-text capitalize">{diagnosis.component.replace('_', ' ')}</span>
                                    <span className="text-xs font-semibold text-admin-text2 capitalize">{diagnosis.condition.replace('_', ' ')}</span>
                                </div>
                                {diagnosis.notes && <p className="text-xs text-admin-text3 mt-1">{diagnosis.notes}</p>}
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

                    <AdminButton type="submit" isLoading={processing}>
                        Save observation
                    </AdminButton>
                </form>
            )}
        </div>
    );
}
