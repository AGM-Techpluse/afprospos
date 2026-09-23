import { FormEvent, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AdminShell from '../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../Components/Admin/AdminPageHead';
import AdminInput from '../../../../Components/Admin/Forms/AdminInput';
import AdminSelect from '../../../../Components/Admin/Forms/AdminSelect';
import AdminButton from '../../../../Components/Admin/AdminButton';
import SkuPreviewCard from '../../../../Components/Admin/SkuPreviewCard';
import AttributeEditor, { emptyAttributes, serializeAttributes, VariantAttributes } from '../../../../Features/Inventory/AttributeEditor';

type Shop = { id: number; name: string; sku_prefix_code: string };

const CONDITIONS = [
    { value: 'new', label: 'New' },
    { value: 'used_grade_a', label: 'Used — Grade A' },
    { value: 'used_grade_b', label: 'Used — Grade B' },
    { value: 'used_grade_c', label: 'Used — Grade C' },
    { value: 'refurbished', label: 'Refurbished' },
];

type VariantRow = {
    attributes: VariantAttributes;
    cost_price_naira: string;
    markup_percent: string;
    selling_price_override_naira: string;
    low_stock_threshold: string;
    initial_quantity: string;
};

function emptyVariantRow(): VariantRow {
    return {
        attributes: emptyAttributes(),
        cost_price_naira: '',
        markup_percent: '20',
        selling_price_override_naira: '',
        low_stock_threshold: '',
        initial_quantity: '',
    };
}

export default function ProductCreate({ shops }: { shops: Shop[] }) {
    const { data, setData, post, transform, processing, errors } = useForm({
        brand: '',
        model: '',
        category: '',
        condition: 'new',
        is_serialized: true,
        cost_price_naira: '',
        markup_percent: '20',
        selling_price_override_naira: '',
        low_stock_threshold: '',
        shop_id: shops[0]?.id.toString() ?? '',
        initial_quantity: '',
    });

    const [imeis, setImeis] = useState<string[]>(['']);
    const [attributes, setAttributes] = useState<VariantAttributes>(emptyAttributes());
    const [hasVariants, setHasVariants] = useState(false);
    const [variants, setVariants] = useState<VariantRow[]>([emptyVariantRow()]);
    const [variantsProcessing, setVariantsProcessing] = useState(false);
    const [variantsErrors, setVariantsErrors] = useState<Record<string, string>>({});

    function updateImei(index: number, value: string) {
        setImeis((current) => current.map((imei, i) => (i === index ? value : imei)));
    }

    function addImeiField() {
        setImeis((current) => [...current, '']);
    }

    function removeImeiField(index: number) {
        setImeis((current) => current.filter((_, i) => i !== index));
    }

    function addVariantRow() {
        setVariants((current) => [...current, emptyVariantRow()]);
    }

    function removeVariantRow(index: number) {
        setVariants((current) => current.filter((_, i) => i !== index));
    }

    function updateVariantRow(index: number, patch: Partial<VariantRow>) {
        setVariants((current) => current.map((row, i) => (i === index ? { ...row, ...patch } : row)));
    }

    function submit(e: FormEvent) {
        e.preventDefault();

        transform((formData) => ({
            brand: formData.brand,
            model: formData.model,
            category: formData.category,
            condition: formData.condition,
            is_serialized: formData.is_serialized,
            cost_price_minor: Math.round(parseFloat(formData.cost_price_naira || '0') * 100),
            markup_percent: parseFloat(formData.markup_percent || '0'),
            selling_price_minor_override: formData.selling_price_override_naira
                ? Math.round(parseFloat(formData.selling_price_override_naira) * 100)
                : null,
            low_stock_threshold: formData.low_stock_threshold ? parseInt(formData.low_stock_threshold, 10) : null,
            shop_id: parseInt(formData.shop_id, 10),
            initial_quantity:
                !formData.is_serialized && formData.initial_quantity ? parseInt(formData.initial_quantity, 10) : null,
            initial_imeis: formData.is_serialized ? imeis.map((imei) => imei.trim()).filter(Boolean) : null,
            attributes: serializeAttributes(attributes),
        }));

        post('/admin/inventory/products');
    }

    function submitVariants(e: FormEvent) {
        e.preventDefault();
        setVariantsProcessing(true);
        setVariantsErrors({});

        router.post(
            '/admin/inventory/products/with-variants',
            {
                brand: data.brand,
                model: data.model,
                category: data.category,
                condition: data.condition,
                shop_id: data.shop_id,
                variants: variants.map((row) => ({
                    attributes: serializeAttributes(row.attributes),
                    cost_price_minor: Math.round(parseFloat(row.cost_price_naira || '0') * 100),
                    markup_percent: parseFloat(row.markup_percent || '0'),
                    selling_price_minor_override: row.selling_price_override_naira
                        ? Math.round(parseFloat(row.selling_price_override_naira) * 100)
                        : null,
                    low_stock_threshold: row.low_stock_threshold ? parseInt(row.low_stock_threshold, 10) : null,
                    initial_quantity: row.initial_quantity ? parseInt(row.initial_quantity, 10) : null,
                })),
            },
            {
                onFinish: () => setVariantsProcessing(false),
                onError: (errs) => setVariantsErrors(errs as Record<string, string>),
            },
        );
    }

    const costPriceMinor = Math.round(parseFloat(data.cost_price_naira || '0') * 100);
    const sellingOverrideMinor = data.selling_price_override_naira
        ? Math.round(parseFloat(data.selling_price_override_naira) * 100)
        : null;

    return (
        <AdminShell>
            <Head title="New product" />

            <AdminPageHead
                title="New product"
                description="Add a device or product to the catalog."
                backHref="/admin/inventory/products"
                backLabel="Back to products"
            />

            <div className="max-w-[992px] mx-auto">
                <div className="mb-6">
                    <AdminInput label="Brand" value={data.brand} onChange={(e) => setData('brand', e.target.value)} error={errors.brand} required />
                    <AdminInput label="Model" value={data.model} onChange={(e) => setData('model', e.target.value)} error={errors.model} required />
                    <AdminInput
                        label="Category"
                        value={data.category}
                        onChange={(e) => setData('category', e.target.value)}
                        error={errors.category}
                        required
                    />
                    <AdminSelect label="Condition" value={data.condition} onChange={(e) => setData('condition', e.target.value)}>
                        {CONDITIONS.map((c) => (
                            <option key={c.value} value={c.value}>
                                {c.label}
                            </option>
                        ))}
                    </AdminSelect>
                    <AdminSelect label="Shop" value={data.shop_id} onChange={(e) => setData('shop_id', e.target.value)} error={errors.shop_id}>
                        {shops.map((shop) => (
                            <option key={shop.id} value={shop.id}>
                                {shop.name} ({shop.sku_prefix_code})
                            </option>
                        ))}
                    </AdminSelect>

                    <div className="mb-5 flex flex-col gap-2">
                        <label className="flex items-center gap-2 text-sm text-admin-text">
                            <input
                                type="checkbox"
                                checked={data.is_serialized}
                                onChange={(e) => {
                                    setData('is_serialized', e.target.checked);
                                    if (e.target.checked) setHasVariants(false);
                                }}
                            />
                            Serialized (tracked by IMEI/serial number)
                        </label>
                        {!data.is_serialized && (
                            <label className="flex items-center gap-2 text-sm text-admin-text">
                                <input type="checkbox" checked={hasVariants} onChange={(e) => setHasVariants(e.target.checked)} />
                                This product has variants (e.g. different brands of the same accessory)
                            </label>
                        )}
                    </div>
                </div>

                {!hasVariants ? (
                    <div className="grid gap-8 lg:grid-cols-[minmax(0,640px)_320px] items-start">
                        <form onSubmit={submit}>
                            <div className="mb-5">
                                <span className="block text-xs font-semibold text-admin-text mb-1.5">Attributes (optional)</span>
                                <AttributeEditor value={attributes} onChange={setAttributes} />
                            </div>

                            <AdminInput
                                label="Cost price (₦)"
                                type="number"
                                step="0.01"
                                value={data.cost_price_naira}
                                onChange={(e) => setData('cost_price_naira', e.target.value)}
                                error={errors.cost_price_minor}
                                required
                            />
                            <AdminInput
                                label="Markup (%)"
                                type="number"
                                step="0.01"
                                value={data.markup_percent}
                                onChange={(e) => setData('markup_percent', e.target.value)}
                                error={errors.markup_percent}
                                required
                            />
                            <AdminInput
                                label="Selling price override (₦, optional)"
                                type="number"
                                step="0.01"
                                value={data.selling_price_override_naira}
                                onChange={(e) => setData('selling_price_override_naira', e.target.value)}
                            />
                            <AdminInput
                                label="Low stock threshold (optional)"
                                type="number"
                                value={data.low_stock_threshold}
                                onChange={(e) => setData('low_stock_threshold', e.target.value)}
                            />

                            {data.is_serialized ? (
                                <div className="mb-6">
                                    <span className="block text-sm font-semibold text-admin-text mb-2">Initial IMEIs</span>
                                    <div className="flex flex-col gap-2">
                                        {imeis.map((imei, index) => (
                                            <div key={index} className="flex items-center gap-2">
                                                <input
                                                    type="text"
                                                    value={imei}
                                                    onChange={(e) => updateImei(index, e.target.value)}
                                                    placeholder="15-digit IMEI"
                                                    className="flex-1 border border-admin-border-strong rounded-admin-button px-admin-field-x py-admin-field-y text-admin-field"
                                                />
                                                {imeis.length > 1 && (
                                                    <button
                                                        type="button"
                                                        onClick={() => removeImeiField(index)}
                                                        className="text-admin-red text-xs underline shrink-0"
                                                    >
                                                        Remove
                                                    </button>
                                                )}
                                            </div>
                                        ))}
                                        <AdminButton type="button" variant="neutral" onClick={addImeiField}>
                                            Add another IMEI
                                        </AdminButton>
                                    </div>
                                </div>
                            ) : (
                                <AdminInput
                                    label="Initial quantity"
                                    type="number"
                                    value={data.initial_quantity}
                                    onChange={(e) => setData('initial_quantity', e.target.value)}
                                />
                            )}

                            <div className="flex items-center gap-2">
                                <AdminButton type="submit" isLoading={processing}>
                                    Create product
                                </AdminButton>
                                <AdminButton type="button" variant="neutral" onClick={() => router.visit('/admin/inventory/products')}>
                                    Cancel
                                </AdminButton>
                            </div>
                        </form>

                        <div className="lg:sticky lg:top-6">
                            <SkuPreviewCard
                                brand={data.brand}
                                model={data.model}
                                category={data.category}
                                isSerialized={data.is_serialized}
                                costPriceMinor={costPriceMinor}
                                markupPercent={parseFloat(data.markup_percent || '0')}
                                sellingPriceOverrideMinor={sellingOverrideMinor}
                            />
                        </div>
                    </div>
                ) : (
                    <form onSubmit={submitVariants} className="max-w-2xl">
                        {variantsErrors.variants && <p className="text-xs text-admin-red mb-3">{variantsErrors.variants}</p>}

                        <div className="flex flex-col gap-4 mb-4">
                            {variants.map((row, index) => (
                                <div key={index} className="border border-admin-border rounded-admin-card p-4">
                                    <div className="flex items-center justify-between mb-2">
                                        <span className="text-sm font-semibold text-admin-text">Variant {index + 1}</span>
                                        {variants.length > 1 && (
                                            <button
                                                type="button"
                                                onClick={() => removeVariantRow(index)}
                                                className="text-admin-red text-xs underline"
                                            >
                                                Remove variant
                                            </button>
                                        )}
                                    </div>

                                    <div className="mb-3">
                                        <AttributeEditor
                                            value={row.attributes}
                                            onChange={(attrs) => updateVariantRow(index, { attributes: attrs })}
                                        />
                                    </div>

                                    <div className="grid sm:grid-cols-2 gap-2 mb-2">
                                        <input
                                            type="number"
                                            step="0.01"
                                            placeholder="Cost price (₦)"
                                            value={row.cost_price_naira}
                                            onChange={(e) => updateVariantRow(index, { cost_price_naira: e.target.value })}
                                            className="border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs"
                                        />
                                        <input
                                            type="number"
                                            step="0.01"
                                            placeholder="Markup (%)"
                                            value={row.markup_percent}
                                            onChange={(e) => updateVariantRow(index, { markup_percent: e.target.value })}
                                            className="border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs"
                                        />
                                        <input
                                            type="number"
                                            step="0.01"
                                            placeholder="Selling price override (₦, optional)"
                                            value={row.selling_price_override_naira}
                                            onChange={(e) => updateVariantRow(index, { selling_price_override_naira: e.target.value })}
                                            className="border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs"
                                        />
                                        <input
                                            type="number"
                                            placeholder="Low stock threshold (optional)"
                                            value={row.low_stock_threshold}
                                            onChange={(e) => updateVariantRow(index, { low_stock_threshold: e.target.value })}
                                            className="border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs"
                                        />
                                    </div>
                                    <input
                                        type="number"
                                        placeholder="Initial quantity"
                                        value={row.initial_quantity}
                                        onChange={(e) => updateVariantRow(index, { initial_quantity: e.target.value })}
                                        className="w-full border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs"
                                    />
                                </div>
                            ))}
                        </div>

                        <div className="flex items-center gap-2">
                            <AdminButton type="button" variant="neutral" onClick={addVariantRow}>
                                Add another variant
                            </AdminButton>
                        </div>

                        <div className="flex items-center gap-2 mt-4">
                            <AdminButton type="submit" isLoading={variantsProcessing}>
                                Create product with {variants.length} variant{variants.length === 1 ? '' : 's'}
                            </AdminButton>
                            <AdminButton type="button" variant="neutral" onClick={() => router.visit('/admin/inventory/products')}>
                                Cancel
                            </AdminButton>
                        </div>
                    </form>
                )}
            </div>
        </AdminShell>
    );
}
