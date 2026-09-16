import { FormEvent, useState } from 'react';
import { router } from '@inertiajs/react';
import Icon from '../../Components/Icons/Icon';

type DeviceBrand = { id: number; name: string };
type DeviceProblemTag = { id: number; label: string };
export type DeviceType = { id: number; label: string; icon: string; brands: DeviceBrand[]; problem_tags: DeviceProblemTag[] };

interface DeviceTypePickerProps {
    deviceTypes: DeviceType[];
    selectedProblemTagIds: number[];
    onSelectBrand: (deviceType: string, brand: string) => void;
    onToggleProblemTag: (id: number) => void;
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
            { label: label.trim(), icon: icon.trim() || 'device-hdd' },
            { preserveScroll: true, preserveState: true, showProgress: false, onSuccess: () => { setLabel(''); setOpen(false); onAdded(); } },
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
            <input value={icon} onChange={(e) => setIcon(e.target.value)} placeholder="icon" className={`${quickAddInputClass} w-24`} />
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
 * A faster path to filling device make/model/problem than typing from
 * scratch — pick a device type, then a brand (pre-fills "Device make",
 * still freely editable), then any fixable problems that apply (feeds
 * `problem_tag_ids`, which also drives the Parts page's suggested-parts
 * list later). All three levels are admin-editable inline here, or in
 * bulk under Settings / Device catalog. Reads from the DB-backed catalog
 * (`Domain\Repair\Application\Queries\DeviceCatalogQuery`) rather than a
 * hardcoded list.
 */
export default function DeviceTypePicker({ deviceTypes, selectedProblemTagIds, onSelectBrand, onToggleProblemTag }: DeviceTypePickerProps) {
    const [selectedTypeId, setSelectedTypeId] = useState<number | null>(null);
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
                <p className="text-xs text-admin-text3 mt-3">Not sure, or something else entirely? Just type the device details in the form directly.</p>
            </div>
        );
    }

    return (
        <div>
            <button
                type="button"
                onClick={() => setSelectedTypeId(null)}
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
                        onClick={() => onSelectBrand(selectedType.label, brand.name)}
                        className="bg-admin-surface border border-admin-border rounded-admin-card p-3 text-sm font-semibold text-admin-text hover:border-admin-blue hover:bg-admin-blue-soft transition-colors"
                    >
                        {brand.name}
                    </button>
                ))}
                <button
                    type="button"
                    onClick={() => onSelectBrand(selectedType.label, '')}
                    className="bg-admin-surface border border-admin-border rounded-admin-card p-3 text-sm font-semibold text-admin-text3 hover:border-admin-blue hover:bg-admin-blue-soft transition-colors"
                >
                    Other / not listed
                </button>
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

            {selectedType.problem_tags.length > 0 && (
                <>
                    <h3 className="text-xs font-semibold text-admin-text2 mb-2">
                        Common problems for {selectedType.label.toLowerCase()} (optional — pick any that apply)
                    </h3>
                    <div className="flex flex-wrap gap-1.5 mb-2">
                        {selectedType.problem_tags.map((tag) => {
                            const checked = selectedProblemTagIds.includes(tag.id);
                            return (
                                <button
                                    key={tag.id}
                                    type="button"
                                    onClick={() => onToggleProblemTag(tag.id)}
                                    className={`text-xs font-semibold rounded-admin-badge px-2.5 py-1 border transition-colors ${
                                        checked
                                            ? 'bg-admin-blue text-white border-admin-blue'
                                            : 'bg-white text-admin-text2 border-admin-border hover:border-admin-blue'
                                    }`}
                                >
                                    {checked && <Icon name="check" className="mr-1" />}
                                    {tag.label}
                                </button>
                            );
                        })}
                    </div>
                </>
            )}
            <QuickAddInline
                placeholder="Add a problem tag"
                onSubmit={(label) =>
                    router.post(
                        '/admin/settings/device-catalog/problem-tags',
                        { device_type_id: selectedType.id, label },
                        { preserveScroll: true, preserveState: true, showProgress: false, onSuccess: refresh },
                    )
                }
            />
        </div>
    );
}
