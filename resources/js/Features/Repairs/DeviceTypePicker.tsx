import { FormEvent, useState } from 'react';
import { router } from '@inertiajs/react';
import Icon from '../../Components/Icons/Icon';
import IconPicker from '../../Components/Icons/IconPicker';

type DeviceProblemTag = { id: number; label: string };
type DeviceModel = { id: number; name: string; fixable_faults: DeviceProblemTag[]; has_model_specific_faults: boolean };
type DeviceBrand = { id: number; name: string; models: DeviceModel[] };
export type DeviceType = { id: number; label: string; icon: string; brands: DeviceBrand[]; problem_tags: DeviceProblemTag[] };

interface DeviceTypePickerProps {
    deviceTypes: DeviceType[];
    /** Only fires once a model is picked/quick-added — model selection is required for intake, unlike the old optional brand-only flow. */
    onSelectModel: (deviceType: string, brand: string, model: string, modelId: number) => void;
}

const quickAddInputClass =
    'border border-admin-border-strong rounded-admin-button px-2.5 py-1.5 text-xs outline-none focus:border-admin-blue focus:ring-2 focus:ring-admin-focus flex-1';

function QuickAddDeviceType({ onAdded }: { onAdded: () => void }) {
    const [open, setOpen] = useState(false);
    const [label, setLabel] = useState('');
    const [icon, setIcon] = useState('device-hdd');

    function submit(e: FormEvent) {
        e.preventDefault();
        if (!label.trim()) return;
        router.post(
            '/admin/settings/device-catalog/types',
            { label: label.trim(), icon: icon || 'device-hdd' },
            { preserveScroll: true, preserveState: true, showProgress: false, onSuccess: () => { setLabel(''); setIcon('device-hdd'); setOpen(false); onAdded(); } },
        );
    }

    if (!open) {
        return (
            <button
                type="button"
                onClick={() => setOpen(true)}
                className="flex flex-col items-center justify-center gap-2 border border-dashed border-admin-border-strong rounded-admin-card p-4 text-admin-text3 hover:border-admin-blue hover:text-admin-blue transition-colors"
            >
                <Icon name="plus-lg" className="text-lg" />
                <span className="text-xs font-semibold">Add device type</span>
            </button>
        );
    }

    return (
        <form onSubmit={submit} className="col-span-2 flex items-center gap-2 bg-admin-surface border border-admin-border rounded-admin-card p-3">
            <IconPicker value={icon} onChange={setIcon} compact />
            <input value={label} onChange={(e) => setLabel(e.target.value)} placeholder="e.g. Drones" autoFocus className={quickAddInputClass} />
            <button type="submit" className="text-xs font-semibold text-admin-blue px-2">Add</button>
            <button type="button" onClick={() => setOpen(false)} className="text-xs text-admin-text3 px-1">Cancel</button>
        </form>
    );
}

function QuickAddInline({ placeholder, onSubmit }: { placeholder: string; onSubmit: (value: string) => void }) {
    const [open, setOpen] = useState(false);
    const [value, setValue] = useState('');

    function submit(e: FormEvent) {
        e.preventDefault();
        if (!value.trim()) return;
        onSubmit(value.trim());
        setValue('');
        setOpen(false);
    }

    if (!open) {
        return (
            <button type="button" onClick={() => setOpen(true)} className="text-xs font-semibold text-admin-blue inline-flex items-center gap-1">
                <Icon name="plus-lg" /> {placeholder}
            </button>
        );
    }

    return (
        <form onSubmit={submit} className="flex items-center gap-1.5">
            <input value={value} onChange={(e) => setValue(e.target.value)} autoFocus className={quickAddInputClass} />
            <button type="submit" className="text-xs font-semibold text-admin-blue px-1">Add</button>
            <button type="button" onClick={() => setOpen(false)} className="text-xs text-admin-text3 px-1">
                <Icon name="x" />
            </button>
        </form>
    );
}

/**
 * Picks device type -> brand -> model (model selection is required —
 * device_model_id is what scopes a diagnosis case's fixable-fault/
 * commission catalog later, on the Show page). Prefills "Device make"/
 * "Device model", still freely editable as display text. All three
 * levels are admin-editable inline here via quick-add, or in bulk under
 * Settings / Device catalog. Reads from the DB-backed catalog
 * (`Domain\Repair\Application\Queries\DeviceCatalogQuery`) rather than a
 * hardcoded list.
 */
export default function DeviceTypePicker({ deviceTypes, onSelectModel }: DeviceTypePickerProps) {
    const [selectedTypeId, setSelectedTypeId] = useState<number | null>(null);
    const [selectedBrand, setSelectedBrand] = useState<DeviceBrand | null>(null);
    const [selectedModelId, setSelectedModelId] = useState<number | null>(null);
    const selectedType = deviceTypes.find((type) => type.id === selectedTypeId) ?? null;

    function refresh() {
        router.reload({ only: ['deviceCatalog'], showProgress: false });
    }

    if (selectedType === null) {
        return (
            <div>
                <h2 className="text-sm font-semibold text-admin-text mb-3">What kind of device?</h2>
                <div className="grid grid-cols-2 gap-2">
                    {deviceTypes.map((type) => (
                        <button
                            key={type.id}
                            type="button"
                            onClick={() => setSelectedTypeId(type.id)}
                            className="flex flex-col items-center justify-center gap-2 bg-admin-surface border border-admin-border rounded-admin-card p-4 hover:border-admin-blue hover:bg-admin-blue-soft transition-colors"
                        >
                            <div className="w-10 h-10 rounded-full bg-admin-blue-soft text-admin-blue flex items-center justify-center text-lg">
                                <Icon name={type.icon} />
                            </div>
                            <span className="text-xs font-semibold text-admin-text text-center">{type.label}</span>
                        </button>
                    ))}
                    <QuickAddDeviceType onAdded={refresh} />
                </div>
                <p className="text-xs text-admin-text3 mt-3">Don't see the device type? Add it above — a model must be picked (or quick-added) before this diagnosis case can be saved.</p>
            </div>
        );
    }

    return (
        <div>
            <button
                type="button"
                onClick={() => {
                    setSelectedBrand(null);
                    setSelectedTypeId(null);
                }}
                className="flex items-center gap-1 text-xs font-semibold text-admin-blue mb-3"
            >
                <Icon name="chevron-left" />
                Back to device types
            </button>

            <h2 className="text-sm font-semibold text-admin-text mb-3">{selectedType.label} — pick a brand</h2>
            <div className="grid grid-cols-2 gap-2 mb-2">
                {selectedType.brands.map((brand) => (
                    <button
                        key={brand.id}
                        type="button"
                        onClick={() => {
                            setSelectedBrand(brand);
                            setSelectedModelId(null);
                        }}
                        className={`bg-admin-surface border rounded-admin-card p-3 text-sm font-semibold text-admin-text hover:border-admin-blue hover:bg-admin-blue-soft transition-colors ${
                            selectedBrand?.id === brand.id ? 'border-admin-blue bg-admin-blue-soft' : 'border-admin-border'
                        }`}
                    >
                        {brand.name}
                    </button>
                ))}
            </div>
            <div className="mb-5">
                <QuickAddInline
                    placeholder="Add a brand"
                    onSubmit={(name) =>
                        router.post(
                            '/admin/settings/device-catalog/brands',
                            { device_type_id: selectedType.id, name },
                            { preserveScroll: true, preserveState: true, showProgress: false, onSuccess: refresh },
                        )
                    }
                />
            </div>

            {selectedBrand !== null && (
                <div className="mb-5">
                    <h3 className="text-xs font-semibold text-admin-text2 mb-2">
                        Model for {selectedBrand.name} (required)
                    </h3>
                    {selectedBrand.models.length > 0 && (
                        <div className="flex flex-wrap gap-1.5 mb-2">
                            {selectedBrand.models.map((model) => (
                                <button
                                    key={model.id}
                                    type="button"
                                    onClick={() => {
                                        setSelectedModelId(model.id);
                                        onSelectModel(selectedType.label, selectedBrand.name, model.name, model.id);
                                    }}
                                    className={`text-xs font-semibold rounded-admin-badge px-2.5 py-1 border transition-colors ${
                                        selectedModelId === model.id
                                            ? 'bg-admin-blue text-white border-admin-blue'
                                            : 'bg-white text-admin-text2 border-admin-border hover:border-admin-blue'
                                    }`}
                                >
                                    {selectedModelId === model.id && <Icon name="check" className="mr-1" />}
                                    {model.name}
                                </button>
                            ))}
                        </div>
                    )}
                    <QuickAddInline
                        placeholder="Add a model"
                        onSubmit={(name) =>
                            router.post(
                                '/admin/settings/device-catalog/models',
                                { device_brand_id: selectedBrand.id, name },
                                { preserveScroll: true, preserveState: true, showProgress: false, onSuccess: refresh }
                            )
                        }
                    />
                    <p className="text-xs text-admin-text3 mt-1.5">After adding, click the new model above to select it.</p>
                </div>
            )}
        </div>
    );
}
