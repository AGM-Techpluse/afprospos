export type VariantAttributes = {
    brand: string;
    color: string;
    material: string;
    compatibility: string;
    custom: { key: string; value: string }[];
};

export function emptyAttributes(): VariantAttributes {
    return { brand: '', color: '', material: '', compatibility: '', custom: [] };
}

/** Converts the editor's shape into the {brand,color,material,compatibility,custom:{key:value}} JSON the backend expects, dropping blanks. */
export function serializeAttributes(attrs: VariantAttributes): Record<string, unknown> {
    const result: Record<string, unknown> = {};
    if (attrs.brand.trim()) result.brand = attrs.brand.trim();
    if (attrs.color.trim()) result.color = attrs.color.trim();
    if (attrs.material.trim()) result.material = attrs.material.trim();
    if (attrs.compatibility.trim()) result.compatibility = attrs.compatibility.trim();

    const custom: Record<string, string> = {};
    attrs.custom.forEach(({ key, value }) => {
        if (key.trim() && value.trim()) custom[key.trim()] = value.trim();
    });
    if (Object.keys(custom).length > 0) result.custom = custom;

    return result;
}

interface AttributeEditorProps {
    value: VariantAttributes;
    onChange: (value: VariantAttributes) => void;
}

/** Fixed common fields (brand/color/material/compatibility) + free-form custom key/value rows, for a non-serialized product variant (e.g. a screenguard's brand, or an accessory's compatible-device list). */
export default function AttributeEditor({ value, onChange }: AttributeEditorProps) {
    function set<K extends keyof VariantAttributes>(key: K, val: VariantAttributes[K]) {
        onChange({ ...value, [key]: val });
    }

    function addCustomRow() {
        onChange({ ...value, custom: [...value.custom, { key: '', value: '' }] });
    }

    function updateCustomRow(index: number, field: 'key' | 'value', val: string) {
        const next = value.custom.map((row, i) => (i === index ? { ...row, [field]: val } : row));
        onChange({ ...value, custom: next });
    }

    function removeCustomRow(index: number) {
        onChange({ ...value, custom: value.custom.filter((_, i) => i !== index) });
    }

    return (
        <div className="border border-admin-border rounded-admin-card p-3">
            <div className="grid grid-cols-2 gap-2 mb-2">
                <input
                    type="text"
                    value={value.brand}
                    onChange={(e) => set('brand', e.target.value)}
                    placeholder="Brand (e.g. Anker)"
                    className="border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs"
                />
                <input
                    type="text"
                    value={value.color}
                    onChange={(e) => set('color', e.target.value)}
                    placeholder="Color"
                    className="border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs"
                />
                <input
                    type="text"
                    value={value.material}
                    onChange={(e) => set('material', e.target.value)}
                    placeholder="Material"
                    className="border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs"
                />
                <input
                    type="text"
                    value={value.compatibility}
                    onChange={(e) => set('compatibility', e.target.value)}
                    placeholder="Compatibility (e.g. iPhone 15/15 Pro)"
                    className="border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs"
                />
            </div>

            {value.custom.length > 0 && (
                <div className="flex flex-col gap-1.5 mb-2">
                    {value.custom.map((row, index) => (
                        <div key={index} className="flex items-center gap-1.5">
                            <input
                                type="text"
                                value={row.key}
                                onChange={(e) => updateCustomRow(index, 'key', e.target.value)}
                                placeholder="Attribute name"
                                className="flex-1 border border-admin-border-strong rounded-admin-button px-2 py-1 text-xs"
                            />
                            <input
                                type="text"
                                value={row.value}
                                onChange={(e) => updateCustomRow(index, 'value', e.target.value)}
                                placeholder="Value"
                                className="flex-1 border border-admin-border-strong rounded-admin-button px-2 py-1 text-xs"
                            />
                            <button
                                type="button"
                                onClick={() => removeCustomRow(index)}
                                className="text-admin-red text-xs underline shrink-0"
                            >
                                Remove
                            </button>
                        </div>
                    ))}
                </div>
            )}

            <button type="button" onClick={addCustomRow} className="text-xs font-semibold text-admin-blue">
                + Add custom attribute
            </button>
        </div>
    );
}
