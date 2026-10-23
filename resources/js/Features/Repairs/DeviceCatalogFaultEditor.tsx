import { FormEvent, useState } from 'react';
import { router } from '@inertiajs/react';
import Icon from '../../Components/Icons/Icon';
import AdminButton from '../../Components/Admin/AdminButton';

export type SuggestedPart = { id: number; sku_id: number; sku_code: string | null; product_name: string | null };
export type DeviceProblemTag = { id: number; label: string; sort_order: number; commission_amount_minor: number | null; suggested_parts: SuggestedPart[] };
export type DeviceModel = { id: number; name: string; sort_order: number; fixable_faults: DeviceProblemTag[]; has_model_specific_faults: boolean };

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
export function ProblemTagList({
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
export function ModelFaultsEditor({ model, deviceTypeLabel }: { model: DeviceModel; deviceTypeLabel: string }) {
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
