import { FormEvent, useState } from 'react';
import { router } from '@inertiajs/react';
import { useConfirm } from '../../hooks/useConfirm';
import Icon from '../../Components/Icons/Icon';
import AdminButton from '../../Components/Admin/AdminButton';
import AdminEmptyState from '../../Components/Admin/AdminEmptyState';

export type SuggestedPart = { id: number; sku_id: number; sku_code: string | null; product_name: string | null };
export type DeviceProblemTag = { id: number; label: string; sort_order: number; commission_amount_minor: number | null; suggested_parts: SuggestedPart[] };
export type DeviceModel = { id: number; name: string; sort_order: number; fixable_faults: DeviceProblemTag[]; has_model_specific_faults: boolean };
export type DeviceBrand = { id: number; name: string; sort_order: number; models: DeviceModel[] };
export type DeviceType = {
    id: number;
    label: string;
    icon: string;
    sort_order: number;
    brands: DeviceBrand[];
    problem_tags: DeviceProblemTag[];
};

const inputClass =
    'border border-admin-border-strong rounded-admin-button px-2.5 py-1.5 text-xs outline-none focus:border-admin-blue focus:ring-2 focus:ring-admin-focus bg-white';

function ChipList({
    items,
    onRemove,
    emptyLabel,
}: {
    items: { id: number; label: string }[];
    onRemove: (id: number) => void;
    emptyLabel: string;
}) {
    if (items.length === 0) {
        return <p className="text-xs text-admin-text3">{emptyLabel}</p>;
    }

    return (
        <div className="flex flex-wrap gap-1.5">
            {items.map((item) => (
                <span
                    key={item.id}
                    className="inline-flex items-center gap-1 bg-admin-hover text-admin-text text-xs rounded-admin-badge px-2 py-1"
                >
                    {item.label}
                    <button type="button" onClick={() => onRemove(item.id)} className="text-admin-text3 hover:text-admin-red" aria-label={`Remove ${item.label}`}>
                        <Icon name="x" />
                    </button>
                </span>
            ))}
        </div>
    );
}

function SuggestedPartsEditor({ problemTag }: { problemTag: DeviceProblemTag }) {
    const [term, setTerm] = useState('');
    const [results, setResults] = useState<{ sku_id: number; sku_code: string; product_name: string }[]>([]);
    const [searching, setSearching] = useState(false);

    function search(value: string) {
        setTerm(value);
        if (value.trim() === '') {
            setResults([]);
            return;
        }
        setSearching(true);
        fetch(`/admin/settings/device-catalog/parts/search?q=${encodeURIComponent(value)}`, { credentials: 'same-origin' })
            .then((response) => response.json())
            .then((json: { results: { sku_id: number; sku_code: string; product_name: string }[] }) => setResults(json.results))
            .finally(() => setSearching(false));
    }

    function attach(skuId: number) {
        router.post(
            `/admin/settings/device-catalog/problem-tags/${problemTag.id}/suggested-parts`,
            { sku_id: skuId },
            { preserveScroll: true, showProgress: false, onSuccess: () => { setTerm(''); setResults([]); } },
        );
    }

    function detach(suggestedPartId: number) {
        router.delete(`/admin/settings/device-catalog/suggested-parts/${suggestedPartId}`, { preserveScroll: true, showProgress: false });
    }

    return (
        <div className="mt-2 pl-3 border-l-2 border-admin-border">
            <div className="text-xs font-semibold text-admin-text2 mb-1">Suggested parts</div>
            <ChipList
                items={problemTag.suggested_parts.map((part) => ({
                    id: part.id,
                    label: part.product_name ?? part.sku_code ?? `SKU #${part.sku_id}`,
                }))}
                onRemove={detach}
                emptyLabel="No suggested parts yet — search below to add one."
            />
            <div className="relative mt-1.5 max-w-xs">
                <input
                    type="text"
                    value={term}
                    onChange={(e) => search(e.target.value)}
                    placeholder="Search parts to suggest…"
                    className={`${inputClass} w-full`}
                />
                {term.trim() !== '' && (
                    <div className="absolute z-10 mt-1 w-full bg-white border border-admin-border rounded-admin-card shadow-admin-modal max-h-48 overflow-y-auto">
                        {searching ? (
                            <p className="text-xs text-admin-text3 p-2">Searching…</p>
                        ) : results.length === 0 ? (
                            <p className="text-xs text-admin-text3 p-2">No matching parts</p>
                        ) : (
                            results.map((result) => (
                                <button
                                    key={result.sku_id}
                                    type="button"
                                    onClick={() => attach(result.sku_id)}
                                    className="w-full text-left px-2.5 py-1.5 text-xs hover:bg-admin-hover"
                                >
                                    {result.product_name} <span className="text-admin-text3">({result.sku_code})</span>
                                </button>
                            ))
                        )}
                    </div>
                )}
            </div>
        </div>
    );
}

/** The flat technician commission paid when a fault tied to this tag is ticked as actually fixed at repair completion. */
function ProblemTagCommissionInput({ problemTag }: { problemTag: DeviceProblemTag }) {
    const [naira, setNaira] = useState(problemTag.commission_amount_minor !== null ? String(problemTag.commission_amount_minor / 100) : '');
    const [saving, setSaving] = useState(false);

    function save() {
        setSaving(true);
        router.put(
            `/admin/settings/device-catalog/problem-tags/${problemTag.id}`,
            {
                label: problemTag.label,
                sort_order: problemTag.sort_order,
                commission_amount_minor: naira.trim() !== '' ? Math.round(parseFloat(naira) * 100) : '',
            },
            { preserveScroll: true, showProgress: false, onFinish: () => setSaving(false) },
        );
    }

    return (
        <div className="flex items-center gap-1.5 mt-1 pl-5">
            <span className="text-xs text-admin-text3">Commission ₦</span>
            <input
                type="number"
                step="0.01"
                min="0"
                value={naira}
                onChange={(e) => setNaira(e.target.value)}
                onBlur={save}
                placeholder="0.00"
                disabled={saving}
                className={`${inputClass} w-24`}
            />
        </div>
    );
}

/**
 * Editable list of fixable-fault tags, reused for both a device type's own
 * (fallback/default) tags and a specific model's override tags — only the
 * `scope` posted to the create endpoint differs.
 */
function ProblemTagList({
    tags,
    scope,
    addPlaceholder,
}: {
    tags: DeviceProblemTag[];
    scope: { device_type_id: number } | { device_model_id: number };
    addPlaceholder: string;
}) {
    const [newProblem, setNewProblem] = useState('');
    const [expandedTagId, setExpandedTagId] = useState<number | null>(null);

    function addProblem(e: FormEvent) {
        e.preventDefault();
        if (!newProblem.trim()) return;
        router.post(
            '/admin/settings/device-catalog/problem-tags',
            { ...scope, label: newProblem.trim(), sort_order: tags.length },
            { preserveScroll: true, showProgress: false, onSuccess: () => setNewProblem('') },
        );
    }

    function removeProblem(id: number) {
        router.delete(`/admin/settings/device-catalog/problem-tags/${id}`, { preserveScroll: true, showProgress: false });
    }

    return (
        <>
            {tags.length === 0 ? (
                <p className="text-xs text-admin-text3">No problem tags yet.</p>
            ) : (
                <ul className="flex flex-col gap-1">
                    {tags.map((tag) => (
                        <li key={tag.id}>
                            <div className="flex items-center justify-between gap-2">
                                <button
                                    type="button"
                                    onClick={() => setExpandedTagId(expandedTagId === tag.id ? null : tag.id)}
                                    className="flex items-center gap-1.5 text-xs text-admin-text hover:text-admin-blue"
                                >
                                    <Icon name={expandedTagId === tag.id ? 'chevron-down' : 'chevron-right'} />
                                    {tag.label}
                                    <span className="text-admin-text3">({tag.suggested_parts.length})</span>
                                </button>
                                <button type="button" onClick={() => removeProblem(tag.id)} className="text-admin-text3 hover:text-admin-red" aria-label={`Remove ${tag.label}`}>
                                    <Icon name="x" />
                                </button>
                            </div>
                            <ProblemTagCommissionInput problemTag={tag} />
                            {expandedTagId === tag.id && <SuggestedPartsEditor problemTag={tag} />}
                        </li>
                    ))}
                </ul>
            )}
            <form onSubmit={addProblem} className="flex items-center gap-1.5 mt-2">
                <input value={newProblem} onChange={(e) => setNewProblem(e.target.value)} placeholder={addPlaceholder} className={`${inputClass} min-w-0 flex-1`} />
                <AdminButton type="submit" variant="neutral">Add</AdminButton>
            </form>
        </>
    );
}

/** A model's own fixable faults, or (while it has none of its own) its device type's fallback list shown read-only until an override is added. */
function ModelFaultsEditor({ model, deviceTypeLabel }: { model: DeviceModel; deviceTypeLabel: string }) {
    if (model.has_model_specific_faults) {
        return (
            <div className="mt-2 pl-3 border-l-2 border-admin-border">
                <ProblemTagList tags={model.fixable_faults} scope={{ device_model_id: model.id }} addPlaceholder="Add another override…" />
            </div>
        );
    }

    return (
        <div className="mt-2 pl-3 border-l-2 border-admin-border">
            <p className="text-xs text-admin-text3 mb-1.5">Using {deviceTypeLabel}&apos;s default faults — add one below to override for this model only.</p>
            {model.fixable_faults.length > 0 && (
                <ul className="flex flex-col gap-1 mb-2">
                    {model.fixable_faults.map((tag) => (
                        <li key={tag.id} className="text-xs text-admin-text2">
                            {tag.label}
                            {tag.commission_amount_minor !== null && (
                                <span className="text-admin-text3"> — ₦{(tag.commission_amount_minor / 100).toFixed(2)}</span>
                            )}
                        </li>
                    ))}
                </ul>
            )}
            <ProblemTagList tags={[]} scope={{ device_model_id: model.id }} addPlaceholder="Add a model-specific fault…" />
        </div>
    );
}

function DeviceTypeCard({ deviceType }: { deviceType: DeviceType }) {
    const confirm = useConfirm();
    const [editing, setEditing] = useState(false);
    const [label, setLabel] = useState(deviceType.label);
    const [icon, setIcon] = useState(deviceType.icon);
    const [newBrand, setNewBrand] = useState('');
    const [newModelByBrand, setNewModelByBrand] = useState<Record<number, string>>({});
    const [expandedModelId, setExpandedModelId] = useState<number | null>(null);

    function saveType(e: FormEvent) {
        e.preventDefault();
        router.put(
            `/admin/settings/device-catalog/types/${deviceType.id}`,
            { label, icon, sort_order: deviceType.sort_order },
            { preserveScroll: true, showProgress: false, onSuccess: () => setEditing(false) },
        );
    }

    async function deleteType() {
        const confirmed = await confirm({
            kind: 'danger',
            title: `Delete "${deviceType.label}"?`,
            body: 'This also removes its brands and problem tags. Repairs already created keep their own device details — nothing on an existing repair job changes.',
            confirmLabel: 'Delete device type',
        });
        if (confirmed) {
            router.delete(`/admin/settings/device-catalog/types/${deviceType.id}`, { preserveScroll: true, showProgress: false });
        }
    }

    function addBrand(e: FormEvent) {
        e.preventDefault();
        if (!newBrand.trim()) return;
        router.post(
            '/admin/settings/device-catalog/brands',
            { device_type_id: deviceType.id, name: newBrand.trim(), sort_order: deviceType.brands.length },
            { preserveScroll: true, showProgress: false, onSuccess: () => setNewBrand('') },
        );
    }

    function removeBrand(id: number) {
        router.delete(`/admin/settings/device-catalog/brands/${id}`, { preserveScroll: true, showProgress: false });
    }

    function addModel(e: FormEvent, brand: DeviceBrand) {
        e.preventDefault();
        const name = (newModelByBrand[brand.id] ?? '').trim();
        if (!name) return;
        router.post(
            '/admin/settings/device-catalog/models',
            { device_brand_id: brand.id, name, sort_order: brand.models.length },
            {
                preserveScroll: true,
                showProgress: false,
                onSuccess: () => setNewModelByBrand((values) => ({ ...values, [brand.id]: '' })),
            },
        );
    }

    function removeModel(id: number) {
        router.delete(`/admin/settings/device-catalog/models/${id}`, { preserveScroll: true, showProgress: false });
    }

    return (
        <div className="bg-admin-surface border border-admin-border rounded-admin-card p-5">
            <div className="flex items-start justify-between gap-2 mb-4">
                {editing ? (
                    <form onSubmit={saveType} className="flex flex-col gap-2 sm:flex-row sm:items-center flex-1 min-w-0">
                        <input
                            value={icon}
                            onChange={(e) => setIcon(e.target.value)}
                            placeholder="bootstrap-icon-name"
                            className={`${inputClass} w-full sm:w-28`}
                        />
                        <input value={label} onChange={(e) => setLabel(e.target.value)} className={`${inputClass} min-w-0 sm:flex-1`} />
                        <div className="flex items-center gap-2">
                            <AdminButton type="submit" variant="neutral">Save</AdminButton>
                            <AdminButton type="button" variant="neutral" onClick={() => setEditing(false)}>Cancel</AdminButton>
                        </div>
                    </form>
                ) : (
                    <div className="flex items-center gap-2.5">
                        <div className="w-9 h-9 rounded-full bg-admin-blue-soft text-admin-blue flex items-center justify-center">
                            <Icon name={deviceType.icon} />
                        </div>
                        <h3 className="text-sm font-semibold text-admin-text">{deviceType.label}</h3>
                    </div>
                )}
                {!editing && (
                    <div className="flex items-center gap-1 shrink-0">
                        <button type="button" onClick={() => setEditing(true)} className="text-admin-text3 hover:text-admin-blue p-1" aria-label="Edit device type">
                            <Icon name="pencil" />
                        </button>
                        <button type="button" onClick={deleteType} className="text-admin-text3 hover:text-admin-red p-1" aria-label="Delete device type">
                            <Icon name="trash" />
                        </button>
                    </div>
                )}
            </div>

            <div className="grid sm:grid-cols-2 gap-5">
                <div>
                    <div className="text-xs font-semibold text-admin-text2 mb-1.5">Brands</div>
                    {deviceType.brands.length === 0 ? (
                        <p className="text-xs text-admin-text3">No brands yet.</p>
                    ) : (
                        <ul className="flex flex-col gap-3">
                            {deviceType.brands.map((brand) => (
                                <li key={brand.id} className="border border-admin-border rounded-admin-card p-2.5">
                                    <div className="flex items-center justify-between gap-2">
                                        <span className="text-xs font-semibold text-admin-text">{brand.name}</span>
                                        <button type="button" onClick={() => removeBrand(brand.id)} className="text-admin-text3 hover:text-admin-red" aria-label={`Remove ${brand.name}`}>
                                            <Icon name="x" />
                                        </button>
                                    </div>
                                    <div className="mt-2">
                                        {brand.models.length === 0 ? (
                                            <p className="text-xs text-admin-text3">No models yet.</p>
                                        ) : (
                                            <ul className="flex flex-col gap-1">
                                                {brand.models.map((model) => (
                                                    <li key={model.id}>
                                                        <div className="flex items-center justify-between gap-2">
                                                            <button
                                                                type="button"
                                                                onClick={() => setExpandedModelId(expandedModelId === model.id ? null : model.id)}
                                                                className="flex items-center gap-1.5 text-xs text-admin-text hover:text-admin-blue"
                                                            >
                                                                <Icon name={expandedModelId === model.id ? 'chevron-down' : 'chevron-right'} />
                                                                {model.name}
                                                                {model.has_model_specific_faults && (
                                                                    <span className="text-admin-text3">({model.fixable_faults.length} own faults)</span>
                                                                )}
                                                            </button>
                                                            <button type="button" onClick={() => removeModel(model.id)} className="text-admin-text3 hover:text-admin-red" aria-label={`Remove ${model.name}`}>
                                                                <Icon name="x" />
                                                            </button>
                                                        </div>
                                                        {expandedModelId === model.id && <ModelFaultsEditor model={model} deviceTypeLabel={deviceType.label} />}
                                                    </li>
                                                ))}
                                            </ul>
                                        )}
                                    </div>
                                    <form onSubmit={(e) => addModel(e, brand)} className="flex items-center gap-1.5 mt-2">
                                        <input
                                            value={newModelByBrand[brand.id] ?? ''}
                                            onChange={(e) => setNewModelByBrand((values) => ({ ...values, [brand.id]: e.target.value }))}
                                            placeholder="Add a model..."
                                            className={`${inputClass} min-w-0 flex-1`}
                                        />
                                        <AdminButton type="submit" variant="neutral">Add</AdminButton>
                                    </form>
                                </li>
                            ))}
                        </ul>
                    )}
                    <form onSubmit={addBrand} className="flex items-center gap-1.5 mt-2">
                        <input value={newBrand} onChange={(e) => setNewBrand(e.target.value)} placeholder="Add a brand…" className={`${inputClass} min-w-0 flex-1`} />
                        <AdminButton type="submit" variant="neutral">Add</AdminButton>
                    </form>
                </div>

                <div>
                    <div className="text-xs font-semibold text-admin-text2 mb-1.5">Fixable problems (default — applies to any model with no overrides of its own)</div>
                    <ProblemTagList tags={deviceType.problem_tags} scope={{ device_type_id: deviceType.id }} addPlaceholder="Add a problem tag…" />
                </div>
            </div>
        </div>
    );
}

interface DeviceCatalogManagerProps {
    deviceTypes: DeviceType[];
}

/** Full CRUD for the repair-intake device catalog (Device Type -> Brand / Problem tag -> suggested parts). Backs Admin/Settings/DeviceCatalog/Index.tsx; the same Commands also back the inline quick-add on Repairs/Create.tsx. */
export default function DeviceCatalogManager({ deviceTypes }: DeviceCatalogManagerProps) {
    const [newTypeOpen, setNewTypeOpen] = useState(false);
    const [newLabel, setNewLabel] = useState('');
    const [newIcon, setNewIcon] = useState('device-hdd');

    function addType(e: FormEvent) {
        e.preventDefault();
        if (!newLabel.trim()) return;
        router.post(
            '/admin/settings/device-catalog/types',
            { label: newLabel.trim(), icon: newIcon.trim() || 'device-hdd', sort_order: deviceTypes.length },
            {
                preserveScroll: true,
                showProgress: false,
                onSuccess: () => {
                    setNewLabel('');
                    setNewIcon('device-hdd');
                    setNewTypeOpen(false);
                },
            },
        );
    }

    return (
        <div className="flex flex-col gap-4">
            {deviceTypes.length === 0 && !newTypeOpen && (
                <AdminEmptyState icon="phone" title="No device types yet" description="Add one to start building the repair-intake picker." />
            )}

            {deviceTypes.map((deviceType) => (
                <DeviceTypeCard key={deviceType.id} deviceType={deviceType} />
            ))}

            {newTypeOpen ? (
                <form
                    onSubmit={addType}
                    className="bg-admin-surface border border-admin-border rounded-admin-card p-5 flex flex-col gap-2 sm:flex-row sm:items-center"
                >
                    <input
                        value={newIcon}
                        onChange={(e) => setNewIcon(e.target.value)}
                        placeholder="bootstrap-icon-name"
                        className={`${inputClass} w-full sm:w-32`}
                    />
                    <input
                        value={newLabel}
                        onChange={(e) => setNewLabel(e.target.value)}
                        placeholder="e.g. Drones"
                        autoFocus
                        className={`${inputClass} min-w-0 sm:flex-1`}
                    />
                    <div className="flex items-center gap-2">
                        <AdminButton type="submit">Add device type</AdminButton>
                        <AdminButton type="button" variant="neutral" onClick={() => setNewTypeOpen(false)}>Cancel</AdminButton>
                    </div>
                </form>
            ) : (
                <AdminButton type="button" variant="neutral" onClick={() => setNewTypeOpen(true)}>
                    <Icon name="plus-lg" className="mr-1.5" />
                    New device type
                </AdminButton>
            )}
        </div>
    );
}
