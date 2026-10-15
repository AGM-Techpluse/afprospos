import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import AdminSelect from '../../Components/Admin/Forms/AdminSelect';
import AdminInput from '../../Components/Admin/Forms/AdminInput';
import AdminButton from '../../Components/Admin/AdminButton';

export type MasterKind = 'category' | 'unit' | 'supplier';

export interface MasterEntry {
    id: number;
    name: string;
}

interface MasterSelectProps {
    kind: MasterKind;
    label: string;
    options: MasterEntry[];
    /** Category and unit carry the name; supplier carries the id as a string. */
    value: string;
    onChange: (value: string) => void;
    error?: string;
    required?: boolean;
    /** Suppliers are optional on a product, so the dropdown offers a "None" choice. */
    allowEmpty?: boolean;
}

const ADD_LABEL: Record<MasterKind, string> = {
    category: 'Add category',
    unit: 'Add unit',
    supplier: 'Add supplier',
};

/**
 * A dropdown of an admin-managed list with an inline "Add" beside it, so staff
 * never have to leave the product form to create a category, unit or supplier.
 * After the server saves the new entry, Inertia refreshes the option lists and
 * the new entry is selected automatically.
 */
export default function MasterSelect({ kind, label, options, value, onChange, error, required, allowEmpty }: MasterSelectProps) {
    const [adding, setAdding] = useState(false);
    const [name, setName] = useState('');
    const [phone, setPhone] = useState('');
    const [email, setEmail] = useState('');
    const [pendingName, setPendingName] = useState<string | null>(null);
    const [saving, setSaving] = useState(false);
    const [addError, setAddError] = useState<string | undefined>();

    useEffect(() => {
        if (pendingName === null) return;

        const match = options.find((option) => option.name.toLowerCase() === pendingName.toLowerCase());

        if (match) {
            onChange(kind === 'supplier' ? String(match.id) : match.name);
            setPendingName(null);
        }
    }, [options, pendingName, kind, onChange]);

    function save() {
        const trimmed = name.trim();

        if (trimmed === '') return;

        setSaving(true);
        setAddError(undefined);

        router.post(
            '/admin/inventory/masters',
            { kind, name: trimmed, contact_phone: phone, contact_email: email },
            {
                preserveScroll: true,
                preserveState: true,
                only: ['categories', 'units', 'suppliers', 'flash'],
                onSuccess: () => {
                    setPendingName(trimmed);
                    setName('');
                    setPhone('');
                    setEmail('');
                    setAdding(false);
                },
                onError: (errors) => {
                    setAddError(errors.name ?? errors.contact_phone ?? errors.contact_email ?? errors.kind);
                },
                onFinish: () => setSaving(false),
            },
        );
    }

    return (
        <div className="mb-5">
            <AdminSelect label={label} value={value} onChange={(e) => onChange(e.target.value)} error={error} required={required}>
                {allowEmpty ? (
                    <option value="">None</option>
                ) : (
                    value === '' && <option value="">Select…</option>
                )}
                {options.map((option) => (
                    <option key={option.id} value={kind === 'supplier' ? String(option.id) : option.name}>
                        {option.name}
                    </option>
                ))}
            </AdminSelect>

            {!adding ? (
                <button type="button" onClick={() => setAdding(true)} className="mt-1.5 text-xs font-semibold text-admin-blue hover:underline">
                    + {ADD_LABEL[kind]}
                </button>
            ) : (
                <div className="mt-2 flex flex-col gap-2">
                    <AdminInput label={kind === 'supplier' ? 'Supplier name' : `New ${kind} name`} value={name} onChange={(e) => setName(e.target.value)} />
                    {kind === 'supplier' && (
                        <>
                            <AdminInput label="Phone" value={phone} onChange={(e) => setPhone(e.target.value)} />
                            <AdminInput label="Email (optional)" value={email} onChange={(e) => setEmail(e.target.value)} />
                        </>
                    )}
                    {addError && <p className="text-xs text-admin-red">{addError}</p>}
                    <div className="flex gap-2">
                        <AdminButton type="button" onClick={save} disabled={saving || name.trim() === ''}>
                            {saving ? 'Saving…' : 'Save'}
                        </AdminButton>
                        <AdminButton type="button" variant="neutral" onClick={() => { setAdding(false); setAddError(undefined); }}>
                            Cancel
                        </AdminButton>
                    </div>
                </div>
            )}
        </div>
    );
}
