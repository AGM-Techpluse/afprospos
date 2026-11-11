import { FormEvent, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminShell from '../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../Components/Admin/AdminPageHead';
import AdminButton from '../../../../Components/Admin/AdminButton';
import AdminInfoCard from '../../../../Components/Admin/AdminInfoCard';
import Icon from '../../../../Components/Icons/Icon';
import { ModelFaultsEditor, DeviceModel } from '../../../../Features/Repairs/DeviceCatalogFaultEditor';

const GUIDE_STEPS: { icon: string; title: string; body: string }[] = [
    {
        icon: 'magic',
        title: 'New models start on autopilot',
        body: 'A model with no overrides of its own automatically shows this type’s default faults — nothing to set up for it.',
    },
    {
        icon: 'pencil-square',
        title: 'Click any value to edit it',
        body: 'Fault names and commission amounts are editable right in the table — click a cell, type, then press Enter or click away to save.',
    },
    {
        icon: 'collection',
        title: 'Reuse faults instead of retyping',
        body: 'The moment a model gets its first override it stops inheriting the type’s defaults. Use "Choose from existing faults" to bring the shared ones back in one click, alongside anything unique to this model.',
    },
    {
        icon: 'tools',
        title: 'Suggested parts speed up intake',
        body: 'Click a fault’s "Suggested parts" cell to attach the part a technician usually needs — it’ll be pre-suggested the next time this fault comes up.',
    },
];

type DeviceBrand = {
    id: number;
    name: string;
    sort_order: number;
    device_type: { id: number; label: string };
    models: DeviceModel[];
};

const inputClass =
    'border border-admin-border-strong rounded-admin-button px-2.5 py-1.5 text-xs outline-none focus:border-admin-blue focus:ring-2 focus:ring-admin-focus bg-white';

function QuickAddModel({ deviceBrandId, sortOrder }: { deviceBrandId: number; sortOrder: number }) {
    const [name, setName] = useState('');

    function submit(e: FormEvent) {
        e.preventDefault();
        if (!name.trim()) return;
        router.post(
            '/admin/settings/device-catalog/models',
            { device_brand_id: deviceBrandId, name: name.trim(), sort_order: sortOrder },
            { preserveScroll: true, showProgress: false, onSuccess: () => setName('') },
        );
    }

    return (
        <form onSubmit={submit} className="flex items-center gap-1.5 mt-3">
            <input value={name} onChange={(e) => setName(e.target.value)} placeholder="Add a model…" className={`${inputClass} min-w-0 flex-1`} />
            <AdminButton type="submit" variant="neutral">Add</AdminButton>
        </form>
    );
}

export default function DeviceCatalogBrandShow({ deviceBrand }: { deviceBrand: DeviceBrand }) {
    const [expandedModelId, setExpandedModelId] = useState<number | null>(null);

    function removeModel(id: number) {
        router.delete(`/admin/settings/device-catalog/models/${id}`, { preserveScroll: true, showProgress: false });
    }

    return (
        <AdminShell>
            <Head title={deviceBrand.name} />

            <Link
                href={`/admin/settings/device-catalog/types/${deviceBrand.device_type.id}`}
                className="inline-flex items-center gap-1 text-xs font-semibold text-admin-blue mb-3"
            >
                <Icon name="chevron-left" />
                Back to {deviceBrand.device_type.label}
            </Link>

            <AdminPageHead title={deviceBrand.name} description="Models under this brand, each with its own fixable-fault and commission overrides." />

            {/* grid-cols-1 isn't decorative here: without an explicit track, a CSS grid item's default
                min-width:auto refuses to shrink below its content's natural width — the fault table's
                min-w-[460px] would otherwise force this whole grid (both columns) wider than a phone
                screen instead of stacking. grid-cols-1 gives the single mobile column minmax(0,...)
                sizing, so it can actually shrink and let the table scroll internally instead. */}
            <div className="grid grid-cols-1 gap-5 lg:grid-cols-[minmax(0,1fr)_320px] items-start">
                <div className="min-w-0 bg-admin-surface border border-admin-border rounded-admin-card p-5">
                    <div className="text-sm font-semibold text-admin-text mb-3">Models</div>
                    {deviceBrand.models.length === 0 ? (
                        <p className="text-sm text-admin-text3">No models yet.</p>
                    ) : (
                        <ul className="flex flex-col gap-2">
                            {deviceBrand.models.map((model) => (
                                <li key={model.id} className="border border-admin-border rounded-admin-card p-3">
                                    <div className="flex items-center justify-between gap-2">
                                        <button
                                            type="button"
                                            onClick={() => setExpandedModelId(expandedModelId === model.id ? null : model.id)}
                                            className="flex items-center gap-1.5 text-sm font-semibold text-admin-text hover:text-admin-blue"
                                        >
                                            <Icon name={expandedModelId === model.id ? 'chevron-down' : 'chevron-right'} />
                                            {model.name}
                                            {model.has_model_specific_faults && (
                                                <span className="text-xs font-normal text-admin-text3">({model.fixable_faults.length} own faults)</span>
                                            )}
                                        </button>
                                        <button type="button" onClick={() => removeModel(model.id)} className="text-admin-text3 hover:text-admin-red" aria-label={`Remove ${model.name}`}>
                                            <Icon name="x" />
                                        </button>
                                    </div>
                                    {expandedModelId === model.id && (
                                        <ModelFaultsEditor model={model} deviceTypeId={deviceBrand.device_type.id} deviceTypeLabel={deviceBrand.device_type.label} />
                                    )}
                                </li>
                            ))}
                        </ul>
                    )}
                    <QuickAddModel deviceBrandId={deviceBrand.id} sortOrder={deviceBrand.models.length} />
                </div>

                <AdminInfoCard title="How fault overrides work" icon="lightbulb" iconColor="yellow" className="min-w-0 lg:sticky lg:top-4">
                    <ul className="flex flex-col gap-4">
                        {GUIDE_STEPS.map((step) => (
                            <li key={step.title} className="flex items-start gap-3">
                                <span className="w-7 h-7 rounded-admin-button bg-admin-blue-soft text-admin-blue flex items-center justify-center shrink-0 text-sm">
                                    <Icon name={step.icon} />
                                </span>
                                <div className="min-w-0">
                                    <p className="text-xs font-semibold text-admin-text">{step.title}</p>
                                    <p className="text-xs text-admin-text3 mt-0.5 leading-relaxed">{step.body}</p>
                                </div>
                            </li>
                        ))}
                    </ul>
                </AdminInfoCard>
            </div>
        </AdminShell>
    );
}
