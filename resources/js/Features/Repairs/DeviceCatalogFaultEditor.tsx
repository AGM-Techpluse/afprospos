import { FormEvent, useEffect, useRef, useState } from 'react';
import { router } from '@inertiajs/react';
import Icon from '../../Components/Icons/Icon';
import AdminButton from '../../Components/Admin/AdminButton';
import AdminPagination from '../../Components/Admin/AdminPagination';

export type SuggestedPart = { id: number; sku_id: number; sku_code: string | null; product_name: string | null };
export type DeviceProblemTag = { id: number; label: string; sort_order: number; commission_amount_minor: number | null; suggested_parts: SuggestedPart[] };
export type DeviceModel = { id: number; name: string; sort_order: number; fixable_faults: DeviceProblemTag[]; has_model_specific_faults: boolean };

const inputClass =
    'border border-admin-border-strong rounded-admin-button px-2.5 py-1.5 text-xs outline-none focus:border-admin-blue focus:ring-2 focus:ring-admin-focus bg-white';

/** Click-to-edit cell (Cloudflare/MySQL-table style) — shows plain text until clicked, then becomes a focused input; Enter/blur saves, Escape discards. */
function InlineEditCell({
    value,
    onSave,
    type = 'text',
    placeholder,
    displayValue,
}: {
    value: string;
    onSave: (value: string) => void;
    type?: 'text' | 'number';
    placeholder: string;
    /** What to render in read mode when non-empty — defaults to the raw value. */
    displayValue?: string;
}) {
    const [editing, setEditing] = useState(false);
    const [draft, setDraft] = useState(value);
    const inputRef = useRef<HTMLInputElement>(null);

    useEffect(() => {
        if (!editing) setDraft(value);
    }, [value, editing]);

    useEffect(() => {
        if (editing) {
            inputRef.current?.focus();
            inputRef.current?.select();
        }
    }, [editing]);

    function commit() {
        setEditing(false);
        if (draft !== value) onSave(draft);
    }

    if (editing) {
        return (
            <input
                ref={inputRef}
                type={type}
                step={type === 'number' ? '0.01' : undefined}
                min={type === 'number' ? '0' : undefined}
                value={draft}
                onChange={(e) => setDraft(e.target.value)}
                onBlur={commit}
                onKeyDown={(e) => {
                    if (e.key === 'Enter') e.currentTarget.blur();
                    if (e.key === 'Escape') {
                        setDraft(value);
                        setEditing(false);
                    }
                }}
                placeholder={placeholder}
                className={`${inputClass} w-full`}
            />
        );
    }

    return (
        <button
            type="button"
            onClick={() => setEditing(true)}
            className="w-full text-left px-1.5 py-1 rounded-admin-button hover:bg-admin-hover transition-colors truncate"
        >
            {value.trim() !== '' ? (displayValue ?? value) : <span className="text-admin-text3">{placeholder}</span>}
        </button>
    );
}

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
 * Lets a model pick up fault labels already typed for a sibling model (or
 * the type's own defaults) under the same device type, instead of retyping
 * them — bulk-clones the selected labels onto this model in one request.
 */
function ExistingFaultsPicker({
    deviceTypeId,
    deviceModelId,
    existingLabels,
    startingSortOrder,
}: {
    deviceTypeId: number;
    deviceModelId: number;
    existingLabels: string[];
    startingSortOrder: number;
}) {
    const [open, setOpen] = useState(false);
    const [term, setTerm] = useState('');
    const [labels, setLabels] = useState<string[]>([]);
    const [loading, setLoading] = useState(false);
    const [selected, setSelected] = useState<Set<string>>(new Set());
    const [submitting, setSubmitting] = useState(false);

    function load(searchTerm: string) {
        setLoading(true);
        fetch(
            `/admin/settings/device-catalog/problem-tags/suggestions?device_type_id=${deviceTypeId}&term=${encodeURIComponent(searchTerm)}`,
            { credentials: 'same-origin' },
        )
            .then((response) => response.json())
            .then((json: { labels: string[] }) => setLabels(json.labels))
            .finally(() => setLoading(false));
    }

    function toggleOpen() {
        if (!open) load(term);
        setOpen(!open);
    }

    function search(value: string) {
        setTerm(value);
        load(value);
    }

    function toggleLabel(label: string) {
        setSelected((prev) => {
            const next = new Set(prev);
            if (next.has(label)) {
                next.delete(label);
            } else {
                next.add(label);
            }
            return next;
        });
    }

    function submit() {
        if (selected.size === 0) return;
        setSubmitting(true);
        router.post(
            '/admin/settings/device-catalog/problem-tags/bulk',
            { device_model_id: deviceModelId, labels: Array.from(selected), starting_sort_order: startingSortOrder },
            {
                preserveScroll: true,
                showProgress: false,
                onSuccess: () => { setSelected(new Set()); setOpen(false); setTerm(''); },
                onFinish: () => setSubmitting(false),
            },
        );
    }

    const existingLower = new Set(existingLabels.map((label) => label.toLowerCase()));
    const selectable = labels.filter((label) => !existingLower.has(label.toLowerCase()));

    if (!open) {
        return (
            <AdminButton type="button" variant="neutral" size="sm" onClick={toggleOpen} className="mt-2">
                <Icon name="plus-lg" className="mr-1" />
                Choose from existing faults
            </AdminButton>
        );
    }

    return (
        <div className="mt-2 border border-admin-border rounded-admin-card p-2.5 max-w-xs bg-admin-surface">
            <input
                type="text"
                value={term}
                onChange={(e) => search(e.target.value)}
                placeholder="Search faults…"
                className={`${inputClass} w-full mb-2`}
            />
            <div className="max-h-40 overflow-y-auto flex flex-col gap-1 mb-2">
                {loading ? (
                    <p className="text-xs text-admin-text3">Loading…</p>
                ) : selectable.length === 0 ? (
                    <p className="text-xs text-admin-text3">No other faults to choose from.</p>
                ) : (
                    selectable.map((label) => (
                        <label key={label} className="flex items-center gap-1.5 text-xs text-admin-text">
                            <input type="checkbox" checked={selected.has(label)} onChange={() => toggleLabel(label)} />
                            {label}
                        </label>
                    ))
                )}
            </div>
            <div className="flex items-center gap-1.5">
                <AdminButton type="button" variant="neutral" onClick={submit} disabled={selected.size === 0 || submitting}>
                    Add {selected.size > 0 ? selected.size : ''} selected
                </AdminButton>
                <button type="button" onClick={() => setOpen(false)} className="text-xs text-admin-text3 hover:text-admin-text">
                    Cancel
                </button>
            </div>
        </div>
    );
}

/** One fault row — label and commission are click-to-edit in place (Cloudflare/MySQL-table style); suggested parts expand into a second row below when clicked. */
function FaultRow({
    tag,
    onUpdate,
    onRemove,
}: {
    tag: DeviceProblemTag;
    onUpdate: (patch: { label?: string; commission_amount_minor?: number | null }) => void;
    onRemove: () => void;
}) {
    const [partsOpen, setPartsOpen] = useState(false);
    const commissionValue = tag.commission_amount_minor !== null ? String(tag.commission_amount_minor / 100) : '';

    return (
        <>
            <tr className="border-b border-admin-border last:border-b-0 hover:bg-admin-hover/60">
                <td className="px-3 py-1.5 align-middle">
                    <InlineEditCell value={tag.label} onSave={(label) => onUpdate({ label })} placeholder="Fault name" />
                </td>
                <td className="px-3 py-1.5 align-middle w-28">
                    <InlineEditCell
                        value={commissionValue}
                        onSave={(naira) => onUpdate({ commission_amount_minor: naira.trim() !== '' ? Math.round(parseFloat(naira) * 100) : null })}
                        type="number"
                        placeholder="₦0.00"
                        displayValue={commissionValue !== '' ? `₦${parseFloat(commissionValue).toLocaleString()}` : undefined}
                    />
                </td>
                <td className="px-3 py-1.5 align-middle w-56">
                    <button type="button" onClick={() => setPartsOpen((prev) => !prev)} className="flex flex-wrap items-center gap-1 w-full text-left">
                        {tag.suggested_parts.length === 0 ? (
                            <span className="text-xs text-admin-text3">No parts yet</span>
                        ) : (
                            <>
                                {tag.suggested_parts.slice(0, 2).map((part) => (
                                    <span key={part.id} className="inline-flex items-center bg-admin-hover text-admin-text text-[11px] rounded-admin-badge px-1.5 py-0.5 truncate max-w-[110px]">
                                        {part.product_name ?? part.sku_code ?? `SKU #${part.sku_id}`}
                                    </span>
                                ))}
                                {tag.suggested_parts.length > 2 && (
                                    <span className="text-[11px] text-admin-text3 shrink-0">+{tag.suggested_parts.length - 2}</span>
                                )}
                            </>
                        )}
                        <Icon name={partsOpen ? 'chevron-up' : 'chevron-down'} className="text-admin-text3 text-[10px] shrink-0 ml-auto" />
                    </button>
                </td>
                <td className="pl-1 pr-3 py-1.5 align-middle w-8 text-right">
                    <button type="button" onClick={onRemove} className="text-admin-text3 hover:text-admin-red" aria-label={`Remove ${tag.label}`}>
                        <Icon name="x" />
                    </button>
                </td>
            </tr>
            {partsOpen && (
                <tr className="border-b border-admin-border last:border-b-0 bg-admin-hover/30">
                    <td colSpan={4} className="px-3 pb-3 pt-1">
                        <SuggestedPartsEditor problemTag={tag} />
                    </td>
                </tr>
            )}
        </>
    );
}

/**
 * A model's own fault overrides as a real table — scrollable within a fixed
 * height (~5 rows) rather than growing the page, with client-side
 * pagination once a model genuinely accumulates more than one page's worth
 * (all its faults already arrive with the page, so no server round-trip is
 * needed to turn the page).
 */
function FaultsTable({ tags, deviceModelId }: { tags: DeviceProblemTag[]; deviceModelId: number }) {
    const [page, setPage] = useState(1);
    const pageSize = 10;
    const totalPages = Math.max(1, Math.ceil(tags.length / pageSize));
    const currentPage = Math.min(page, totalPages);
    const pageTags = tags.slice((currentPage - 1) * pageSize, currentPage * pageSize);

    function update(tag: DeviceProblemTag, patch: { label?: string; commission_amount_minor?: number | null }) {
        router.put(
            `/admin/settings/device-catalog/problem-tags/${tag.id}`,
            {
                label: patch.label ?? tag.label,
                sort_order: tag.sort_order,
                commission_amount_minor: 'commission_amount_minor' in patch
                    ? (patch.commission_amount_minor ?? '')
                    : (tag.commission_amount_minor ?? ''),
            },
            { preserveScroll: true, showProgress: false },
        );
    }

    function remove(tagId: number) {
        router.delete(`/admin/settings/device-catalog/problem-tags/${tagId}`, { preserveScroll: true, showProgress: false });
    }

    if (tags.length === 0) {
        return <p className="text-xs text-admin-text3">No faults yet — add one below.</p>;
    }

    return (
        <div className="border border-admin-border rounded-admin-card overflow-hidden bg-admin-surface">
            {/* overflow-auto (not just -y): on a narrow phone this table scrolls sideways within its own
                card instead of pushing the whole page wider — the fixed column widths below need more
                room than a phone screen has, same as any dense data table (ResponsiveDataTable's desktop
                table does the same with overflow-x-auto). */}
            <div className="max-h-[230px] overflow-auto">
                <table className="w-full min-w-[460px] text-xs border-collapse">
                    <thead>
                        <tr>
                            <th className="sticky top-0 z-10 bg-admin-table-head text-left font-semibold text-admin-text2 px-3 py-2">Fault</th>
                            <th className="sticky top-0 z-10 bg-admin-table-head text-left font-semibold text-admin-text2 px-3 py-2 w-28">Commission</th>
                            <th className="sticky top-0 z-10 bg-admin-table-head text-left font-semibold text-admin-text2 px-3 py-2 w-56">Suggested parts</th>
                            <th className="sticky top-0 z-10 bg-admin-table-head w-8" aria-hidden="true" />
                        </tr>
                    </thead>
                    <tbody>
                        {pageTags.map((tag) => (
                            <FaultRow key={tag.id} tag={tag} onUpdate={(patch) => update(tag, patch)} onRemove={() => remove(tag.id)} />
                        ))}
                    </tbody>
                </table>
            </div>
            {tags.length > pageSize && (
                <div className="border-t border-admin-border">
                    <AdminPagination currentPage={currentPage} totalItems={tags.length} pageSize={pageSize} onPageChange={setPage} />
                </div>
            )}
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

/** A compact "add a new fault" row — posts straight to the create endpoint, used below the table in both of ModelFaultsEditor's states. */
function AddFaultForm({
    scope,
    placeholder,
    startingSortOrder,
}: {
    scope: { device_model_id: number };
    placeholder: string;
    startingSortOrder: number;
}) {
    const [label, setLabel] = useState('');

    function submit(e: FormEvent) {
        e.preventDefault();
        if (!label.trim()) return;
        router.post(
            '/admin/settings/device-catalog/problem-tags',
            { ...scope, label: label.trim(), sort_order: startingSortOrder },
            { preserveScroll: true, showProgress: false, onSuccess: () => setLabel('') },
        );
    }

    return (
        <form onSubmit={submit} className="flex items-center gap-1.5 mt-2">
            <input value={label} onChange={(e) => setLabel(e.target.value)} placeholder={placeholder} className={`${inputClass} min-w-0 flex-1`} />
            <AdminButton type="submit" variant="neutral" size="sm">
                <Icon name="plus-lg" className="mr-1" />
                Add
            </AdminButton>
        </form>
    );
}

/** A model's own fixable faults as a real editable table, or (while it has none of its own) its device type's fallback list shown read-only until an override is added. */
export function ModelFaultsEditor({ model, deviceTypeId, deviceTypeLabel }: { model: DeviceModel; deviceTypeId: number; deviceTypeLabel: string }) {
    if (model.has_model_specific_faults) {
        return (
            <div className="mt-3 pl-3 border-l-2 border-admin-border">
                <FaultsTable tags={model.fixable_faults} deviceModelId={model.id} />
                <AddFaultForm scope={{ device_model_id: model.id }} placeholder="Add another override…" startingSortOrder={model.fixable_faults.length} />
                <ExistingFaultsPicker
                    deviceTypeId={deviceTypeId}
                    deviceModelId={model.id}
                    existingLabels={model.fixable_faults.map((tag) => tag.label)}
                    startingSortOrder={model.fixable_faults.length}
                />
            </div>
        );
    }

    return (
        <div className="mt-3 pl-3 border-l-2 border-admin-border">
            <div className="flex items-start gap-1.5 mb-2 text-xs text-admin-text3">
                <Icon name="info-circle" className="mt-0.5 shrink-0" />
                <span>Using {deviceTypeLabel}&apos;s default faults — add one below to override for this model only.</span>
            </div>
            {model.fixable_faults.length > 0 && (
                <div className="border border-admin-border rounded-admin-card overflow-hidden mb-2">
                    <table className="w-full text-xs border-collapse">
                        <tbody>
                            {model.fixable_faults.map((tag) => (
                                <tr key={tag.id} className="border-b border-admin-border last:border-b-0">
                                    <td className="px-3 py-1.5 text-admin-text2">{tag.label}</td>
                                    <td className="px-3 py-1.5 text-admin-text3 text-right w-28">
                                        {tag.commission_amount_minor !== null ? `₦${(tag.commission_amount_minor / 100).toLocaleString()}` : '—'}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
            <AddFaultForm scope={{ device_model_id: model.id }} placeholder="Add a model-specific fault…" startingSortOrder={0} />
            <ExistingFaultsPicker
                deviceTypeId={deviceTypeId}
                deviceModelId={model.id}
                existingLabels={[]}
                startingSortOrder={0}
            />
        </div>
    );
}
