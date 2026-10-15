import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminShell from '../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../Components/Admin/AdminPageHead';
import SettingsSubNav from '../../../../Components/Admin/SettingsSubNav';
import AdminInput from '../../../../Components/Admin/Forms/AdminInput';
import AdminButton from '../../../../Components/Admin/AdminButton';
import AdminEmptyState from '../../../../Components/Admin/AdminEmptyState';
import { useConfirm } from '../../../../hooks/useConfirm';
import type { MasterEntry } from '../../../../Features/Inventory/MasterSelect';

interface SupplierEntry extends MasterEntry {
    contact_phone: string | null;
    contact_email: string | null;
}

interface InventoryCatalogProps {
    categories: MasterEntry[];
    units: MasterEntry[];
    suppliers: SupplierEntry[];
}

/** Admin-managed lists that products are filed under. Adding here is the same as the "+ Add" beside each product dropdown. */
export default function InventoryCatalogIndex({ categories, units, suppliers }: InventoryCatalogProps) {
    return (
        <AdminShell>
            <Head title="Inventory catalog" />

            <AdminPageHead
                title="Inventory catalog"
                description="The categories, base units and suppliers that products are filed under. Renaming a category or unit updates the products that use it."
            />

            <SettingsSubNav />

            <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 items-start">
                <ListCard kind="category" title="Categories" entries={categories} placeholder="e.g. Phones, Parts" />
                <ListCard kind="unit" title="Base units" entries={units} placeholder="e.g. pcs, tons, kegs" />
                <ListCard kind="supplier" title="Suppliers" entries={suppliers} placeholder="Supplier name" />
            </div>
        </AdminShell>
    );
}

function ListCard({
    kind,
    title,
    entries,
    placeholder,
}: {
    kind: 'category' | 'unit' | 'supplier';
    title: string;
    entries: (MasterEntry | SupplierEntry)[];
    placeholder: string;
}) {
    const confirm = useConfirm();
    const [newName, setNewName] = useState('');
    const [newPhone, setNewPhone] = useState('');
    const [newEmail, setNewEmail] = useState('');
    const [error, setError] = useState<string | undefined>();
    const [busy, setBusy] = useState(false);

    function add() {
        if (newName.trim() === '') return;

        setBusy(true);
        setError(undefined);

        router.post(
            '/admin/inventory/masters',
            { kind, name: newName.trim(), contact_phone: newPhone, contact_email: newEmail },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setNewName('');
                    setNewPhone('');
                    setNewEmail('');
                },
                onError: (errors) => setError(errors.name ?? errors.contact_phone ?? errors.contact_email),
                onFinish: () => setBusy(false),
            },
        );
    }

    async function remove(entry: MasterEntry) {
        const confirmed = await confirm({
            kind: 'danger',
            title: `Remove "${entry.name}"?`,
            body: 'This only works when no product uses it.',
            confirmLabel: 'Remove',
        });

        if (confirmed) {
            router.delete(`/admin/inventory/masters/${entry.id}`, { preserveScroll: true });
        }
    }

    return (
        <section className="bg-admin-surface border border-admin-border rounded-admin-card p-3 flex flex-col gap-3">
            <div className="flex items-center justify-between">
                <h2 className="text-sm font-semibold text-admin-text">{title}</h2>
                <span className="text-xs text-admin-text3">{entries.length}</span>
            </div>

            {entries.length === 0 ? (
                <AdminEmptyState icon="list" title={`No ${title.toLowerCase()} yet`} />
            ) : (
                <ul className="flex flex-col divide-y divide-admin-border max-h-72 overflow-y-auto -mx-1">
                    {entries.map((entry) => (
                        <EntryRow key={entry.id} entry={entry} kind={kind} onRemove={() => remove(entry)} />
                    ))}
                </ul>
            )}

            <div className="flex flex-col gap-2 pt-3 border-t border-admin-border">
                <AdminInput label={`Add ${kind}`} placeholder={placeholder} value={newName} onChange={(e) => setNewName(e.target.value)} error={error} />
                {kind === 'supplier' && (
                    <div className="grid grid-cols-2 gap-2">
                        <AdminInput label="Phone" value={newPhone} onChange={(e) => setNewPhone(e.target.value)} />
                        <AdminInput label="Email" value={newEmail} onChange={(e) => setNewEmail(e.target.value)} />
                    </div>
                )}
                <div>
                    <AdminButton type="button" onClick={add} disabled={busy || newName.trim() === ''} className="!px-3 !py-1.5 text-xs">
                        Add {kind}
                    </AdminButton>
                </div>
            </div>
        </section>
    );
}

function EntryRow({ entry, kind, onRemove }: { entry: MasterEntry | SupplierEntry; kind: string; onRemove: () => void }) {
    const [editing, setEditing] = useState(false);
    const [name, setName] = useState(entry.name);
    const [error, setError] = useState<string | undefined>();

    function save() {
        router.put(
            `/admin/inventory/masters/${entry.id}`,
            { name: name.trim() },
            {
                preserveScroll: true,
                onSuccess: () => setEditing(false),
                onError: (errors) => setError(errors.name),
            },
        );
    }

    const contact = kind === 'supplier' ? (entry as SupplierEntry) : null;

    if (editing) {
        return (
            <li className="py-2 flex flex-col gap-2">
                <AdminInput label="Name" value={name} onChange={(e) => setName(e.target.value)} error={error} />
                <div className="flex gap-2">
                    <AdminButton type="button" onClick={save} disabled={name.trim() === ''} className="!px-3 !py-1 text-xs">Save</AdminButton>
                    <AdminButton type="button" variant="neutral" className="!px-3 !py-1 text-xs" onClick={() => { setEditing(false); setName(entry.name); setError(undefined); }}>
                        Cancel
                    </AdminButton>
                </div>
            </li>
        );
    }

    return (
        <li className="py-2 px-1 flex items-center justify-between gap-2">
            <div className="min-w-0">
                <span className="block text-sm font-medium text-admin-text truncate">{entry.name}</span>
                {contact && (contact.contact_phone || contact.contact_email) && (
                    <span className="block text-xs text-admin-text2 truncate">
                        {[contact.contact_phone, contact.contact_email].filter(Boolean).join(' · ')}
                    </span>
                )}
            </div>
            <div className="flex gap-1.5 shrink-0">
                <AdminButton type="button" variant="neutral" className="!px-2 !py-1 text-xs" onClick={() => setEditing(true)}>Rename</AdminButton>
                <AdminButton type="button" variant="danger" className="!px-2 !py-1 text-xs" onClick={onRemove}>Remove</AdminButton>
            </div>
        </li>
    );
}
