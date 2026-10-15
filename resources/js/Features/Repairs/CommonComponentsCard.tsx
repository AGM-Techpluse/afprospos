import { useState } from 'react';
import { router } from '@inertiajs/react';
import AdminButton from '../../Components/Admin/AdminButton';

export type CommonComponentOption = { id: number; label: string };

interface CommonComponentsCardProps {
    components: CommonComponentOption[];
    submitUrl?: string;
    diagnosedComponents: string[];
    disabled?: boolean;
    onMarkSelected?: (components: CommonComponentOption[]) => void;
    bare?: boolean;
}

function postComponent(submitUrl: string, component: string): Promise<void> {
    return new Promise((resolve, reject) => {
        router.post(
            submitUrl,
            { component, condition: 'faulty', notes: '' },
            { preserveScroll: true, preserveState: true, showProgress: false, onSuccess: () => resolve(), onError: () => reject() },
        );
    });
}

/** Bulk "mark as faulty" for the catalog-managed quick-check components (Settings > Common components), so a technician isn't retyping the same handful of names every job — anything not listed still goes through the manual form. */
export default function CommonComponentsCard({ components, submitUrl, diagnosedComponents, disabled = false, onMarkSelected, bare = false }: CommonComponentsCardProps) {
    const [selected, setSelected] = useState<number[]>([]);
    const [submitting, setSubmitting] = useState(false);

    const recorded = new Set(diagnosedComponents.map((label) => label.toLowerCase()));
    const available = components.filter((component) => !recorded.has(component.label.toLowerCase()));

    function toggle(id: number) {
        setSelected((current) => (current.includes(id) ? current.filter((value) => value !== id) : [...current, id]));
    }

    function toggleAll() {
        setSelected((current) => (current.length === available.length ? [] : available.map((component) => component.id)));
    }

    async function markSelectedFaulty() {
        const chosen = available.filter((component) => selected.includes(component.id));

        if (onMarkSelected) {
            onMarkSelected(chosen);
            setSelected([]);
            return;
        }

        if (!submitUrl) return;

        setSubmitting(true);
        try {
            for (const component of chosen) {
                await postComponent(submitUrl, component.label);
            }
            setSelected([]);
        } finally {
            setSubmitting(false);
        }
    }

    return (
        <div className={bare ? '' : 'bg-admin-surface border border-admin-border rounded-admin-card p-6'}>
            <h3 className="text-sm font-semibold text-admin-text mb-1">Common components</h3>
            <p className="text-xs text-admin-text3 mb-4">Check off what is faulty, then record them in one go. Anything not checked is treated as working.</p>

            {available.length === 0 ? (
                <p className="text-xs text-admin-text3">All common components have been recorded for this job.</p>
            ) : (
                <div className="flex flex-col gap-2 mb-4">
                    <label className="flex items-center gap-2 text-sm font-semibold text-admin-text">
                        <input
                            type="checkbox"
                            checked={available.length > 0 && selected.length === available.length}
                            onChange={toggleAll}
                            disabled={disabled || submitting}
                        />
                        Select all common components
                    </label>
                    {available.map((component) => (
                        <label key={component.id} className="flex items-center gap-2 text-sm text-admin-text">
                            <input
                                type="checkbox"
                                checked={selected.includes(component.id)}
                                onChange={() => toggle(component.id)}
                                disabled={disabled || submitting}
                            />
                            {component.label}
                        </label>
                    ))}
                </div>
            )}

            {recorded.size > 0 && (
                <p className="text-xs text-admin-text3 mb-4">
                    Already recorded: {[...recorded].join(', ')}
                </p>
            )}

            <AdminButton
                type="button"
                variant="neutral"
                onClick={markSelectedFaulty}
                disabled={disabled || selected.length === 0}
                isLoading={submitting}
                loadingText="Recording..."
            >
                Mark {selected.length > 0 ? selected.length : ''} selected as faulty
            </AdminButton>
        </div>
    );
}
