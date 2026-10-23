import { FormEvent, useState } from 'react';
import { router } from '@inertiajs/react';
import { useConfirm } from '../../hooks/useConfirm';
import Icon from '../../Components/Icons/Icon';
import AdminButton from '../../Components/Admin/AdminButton';
import AdminEmptyState from '../../Components/Admin/AdminEmptyState';

export type CommonComponent = { id: number; label: string; sort_order: number; commission_amount_minor: number | null };

const inputClass =
    'border border-admin-border-strong rounded-admin-button px-2.5 py-1.5 text-xs outline-none focus:border-admin-blue focus:ring-2 focus:ring-admin-focus bg-white';

function CommonComponentRow({ component }: { component: CommonComponent }) {
    const confirm = useConfirm();
    const [editing, setEditing] = useState(false);
    const [label, setLabel] = useState(component.label);
    const [commissionNaira, setCommissionNaira] = useState(
        component.commission_amount_minor !== null ? String(component.commission_amount_minor / 100) : '',
    );

    function save(e: FormEvent) {
        e.preventDefault();
        router.put(
            `/admin/settings/common-components/${component.id}`,
            {
                label,
                sort_order: component.sort_order,
                commission_amount_minor: commissionNaira.trim() !== '' ? Math.round(parseFloat(commissionNaira) * 100) : '',
            },
            { preserveScroll: true, showProgress: false, onSuccess: () => setEditing(false) },
        );
    }

    async function remove() {
        const confirmed = await confirm({
            kind: 'danger',
            title: `Delete "${component.label}"?`,
            body: 'Repairs that already recorded this component keep their own record of it — nothing on an existing repair changes.',
            confirmLabel: 'Delete component',
        });
        if (confirmed) {
            router.delete(`/admin/settings/common-components/${component.id}`, { preserveScroll: true, showProgress: false });
        }
    }

    if (editing) {
        return (
            <form onSubmit={save} className="border border-admin-border rounded-admin-card p-3 flex flex-col gap-2 sm:flex-row sm:items-center">
                <input value={label} onChange={(e) => setLabel(e.target.value)} className={`${inputClass} min-w-0 sm:flex-1`} />
                <div className="flex items-center gap-1.5">
                    <span className="text-xs text-admin-text3 shrink-0">Commission ₦</span>
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        value={commissionNaira}
                        onChange={(e) => setCommissionNaira(e.target.value)}
                        placeholder="0.00"
                        className={`${inputClass} w-24`}
                    />
                </div>
                <div className="flex items-center gap-2 shrink-0">
                    <AdminButton type="submit" variant="neutral">Save</AdminButton>
                    <AdminButton type="button" variant="neutral" onClick={() => setEditing(false)}>Cancel</AdminButton>
                </div>
            </form>
        );
    }

    return (
        <div className="border border-admin-border rounded-admin-card p-3 flex items-center justify-between gap-2">
            <div className="min-w-0">
                <span className="text-sm text-admin-text">{component.label}</span>
                <span className="text-xs text-admin-text3 ml-2">
                    {component.commission_amount_minor !== null ? `₦${(component.commission_amount_minor / 100).toLocaleString()} commission` : 'Not commissionable'}
                </span>
            </div>
            <div className="flex items-center gap-1 shrink-0">
                <button type="button" onClick={() => setEditing(true)} className="text-admin-text3 hover:text-admin-blue p-1" aria-label={`Edit ${component.label}`}>
                    <Icon name="pencil" />
                </button>
                <button type="button" onClick={remove} className="text-admin-text3 hover:text-admin-red p-1" aria-label={`Delete ${component.label}`}>
                    <Icon name="trash" />
                </button>
            </div>
        </div>
    );
}

interface CommonComponentManagerProps {
    components: CommonComponent[];
}

/** Flat CRUD for the quick-check component catalog offered at diagnosis-case intake — device-type-agnostic, so no nesting, unlike the Device Catalog's Type->Brand->Model->ProblemTag tree. Backs Admin/Settings/CommonComponents/Index.tsx. */
export default function CommonComponentManager({ components }: CommonComponentManagerProps) {
    const [newLabel, setNewLabel] = useState('');

    function add(e: FormEvent) {
        e.preventDefault();
        if (!newLabel.trim()) return;
        router.post(
            '/admin/settings/common-components',
            { label: newLabel.trim(), sort_order: components.length },
            { preserveScroll: true, showProgress: false, onSuccess: () => setNewLabel('') },
        );
    }

    return (
        <div className="flex flex-col gap-3">
            {components.length === 0 && (
                <AdminEmptyState icon="list-check" title="No common components yet" description="Add one to start building the diagnosis-intake quick-check list." />
            )}

            {components.map((component) => (
                <CommonComponentRow key={component.id} component={component} />
            ))}

            <form onSubmit={add} className="flex items-center gap-1.5 mt-2">
                <input value={newLabel} onChange={(e) => setNewLabel(e.target.value)} placeholder="Add a common component…" className={`${inputClass} min-w-0 flex-1`} />
                <AdminButton type="submit">
                    <Icon name="plus-lg" className="mr-1.5" />
                    Add
                </AdminButton>
            </form>
        </div>
    );
}
