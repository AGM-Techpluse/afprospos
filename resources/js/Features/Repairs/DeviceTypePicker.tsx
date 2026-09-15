import { useState } from 'react';
import Icon from '../../Components/Icons/Icon';

type DeviceType = {
    key: string;
    label: string;
    icon: string;
    brands: string[];
};

const DEVICE_TYPES: DeviceType[] = [
    { key: 'smartphones', label: 'Smartphones', icon: 'phone', brands: ['Apple', 'Samsung', 'Tecno', 'Infinix', 'Xiaomi', 'Google'] },
    { key: 'laptops', label: 'Laptops', icon: 'laptop', brands: ['Apple', 'Dell', 'HP', 'Lenovo', 'Asus'] },
    { key: 'cameras', label: 'Cameras', icon: 'camera', brands: ['Canon', 'Nikon', 'Sony', 'Fujifilm', 'GoPro'] },
    { key: 'consoles', label: 'Consoles', icon: 'controller', brands: ['Sony', 'Microsoft', 'Nintendo'] },
    { key: 'tablets', label: 'iPad / Tablets', icon: 'tablet', brands: ['Apple', 'Samsung', 'Lenovo'] },
    { key: 'watches', label: 'Watches', icon: 'smartwatch', brands: ['Apple', 'Samsung', 'Garmin'] },
];

interface DeviceTypePickerProps {
    onSelectBrand: (deviceType: string, brand: string) => void;
}

/**
 * A faster path to filling device make/model than typing from scratch —
 * pick a device type, then a brand, which pre-fills the form's "Device
 * make" field (still freely editable). Purely a data-entry aid: it does
 * not read from the Inventory catalog, since a repair is for a
 * customer's own device, not shop stock.
 */
export default function DeviceTypePicker({ onSelectBrand }: DeviceTypePickerProps) {
    const [selectedType, setSelectedType] = useState<DeviceType | null>(null);

    if (selectedType === null) {
        return (
            <div>
                <h2 className="text-sm font-semibold text-admin-text mb-3">What kind of device?</h2>
                <div className="grid grid-cols-2 gap-2">
                    {DEVICE_TYPES.map((type) => (
                        <button
                            key={type.key}
                            type="button"
                            onClick={() => setSelectedType(type)}
                            className="flex flex-col items-center justify-center gap-2 bg-admin-surface border border-admin-border rounded-admin-card p-4 hover:border-admin-blue hover:bg-admin-blue-soft transition-colors"
                        >
                            <div className="w-10 h-10 rounded-full bg-admin-blue-soft text-admin-blue flex items-center justify-center text-lg">
                                <Icon name={type.icon} />
                            </div>
                            <span className="text-xs font-semibold text-admin-text text-center">{type.label}</span>
                        </button>
                    ))}
                </div>
                <p className="text-xs text-admin-text3 mt-3">Not sure, or something else entirely? Just type the device details in the form directly.</p>
            </div>
        );
    }

    return (
        <div>
            <button
                type="button"
                onClick={() => setSelectedType(null)}
                className="flex items-center gap-1 text-xs font-semibold text-admin-blue mb-3"
            >
                <Icon name="chevron-left" />
                Back to device types
            </button>

            <h2 className="text-sm font-semibold text-admin-text mb-3">{selectedType.label} — pick a brand</h2>
            <div className="grid grid-cols-2 gap-2">
                {selectedType.brands.map((brand) => (
                    <button
                        key={brand}
                        type="button"
                        onClick={() => onSelectBrand(selectedType.label, brand)}
                        className="bg-admin-surface border border-admin-border rounded-admin-card p-3 text-sm font-semibold text-admin-text hover:border-admin-blue hover:bg-admin-blue-soft transition-colors"
                    >
                        {brand}
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
        </div>
    );
}
