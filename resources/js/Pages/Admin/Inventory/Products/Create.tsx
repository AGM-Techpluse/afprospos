import { FormEvent, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AdminShell from '../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../Components/Admin/AdminPageHead';
import AdminInput from '../../../../Components/Admin/Forms/AdminInput';
import AdminSelect from '../../../../Components/Admin/Forms/AdminSelect';
import AdminButton from '../../../../Components/Admin/AdminButton';
import SkuPreviewCard from '../../../../Components/Admin/SkuPreviewCard';
import AttributeEditor, { emptyAttributes, serializeAttributes, VariantAttributes } from '../../../../Features/Inventory/AttributeEditor';
import MasterSelect, { MasterEntry } from '../../../../Features/Inventory/MasterSelect';

const PURPOSES = [
    { value: 'sale', label: 'Sold to customers' },
    { value: 'repair_part', label: 'Repair part (used in repairs)' },
    { value: 'both', label: 'Both — sold and used in repairs' },
];

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
    initial_imeis: string[];
    initial_serial_numbers: string[];
    is_commissionable: boolean;
    commission_amount_naira: string;
};

function emptyVariantRow(): VariantRow {
    return {
        attributes: emptyAttributes(),
        cost_price_naira: '',
        markup_percent: '',
        selling_price_override_naira: '',
        low_stock_threshold: '',
        initial_quantity: '',
        initial_imeis: [''],
        initial_serial_numbers: [''],
        is_commissionable: false,
        commission_amount_naira: '',
    };
}

export default function ProductCreate({
    shops,
    categories,
    units,
    suppliers,
}: {
    shops: Shop[];
    categories: MasterEntry[];
    units: MasterEntry[];
    suppliers: MasterEntry[];
}) {
    const { data, setData, post, transform, processing, errors: formErrors } = useForm({
        brand: '',
        model: '',
        category: '',
        unit: 'pcs',
        purpose: 'sale',
        supplier_id: '',
        condition: 'new',
        is_serialized: true,
        cost_price_naira: '',
        markup_percent: '',
        selling_price_override_naira: '',
        low_stock_threshold: '',
        shop_id: shops[0]?.id.toString() ?? '',
        initial_quantity: '',
        product_image: null as File | null,
        is_commissionable: false,
        commission_amount_naira: '',
    });
    // The server validates minor-unit field names (`cost_price_minor`,
    // `commission_amount_minor`) that don't match this form's naira-bound field names.
    const errors = formErrors as Record<string, string | undefined>;

    const [imeis, setImeis] = useState<string[]>(['']);
    const [serialNumbers, setSerialNumbers] = useState<string[]>(['']);
    const [attributes, setAttributes] = useState<VariantAttributes>(emptyAttributes());
    const [hasVariants, setHasVariants] = useState(false);
    const [variants, setVariants] = useState<VariantRow[]>([emptyVariantRow()]);
    const [variantsProcessing, setVariantsProcessing] = useState(false);
    const [variantsErrors, setVariantsErrors] = useState<Record<string, string>>({});

    function updateImei(index: number, value: string) {
        setImeis((current) => current.map((imei, i) => (i === index ? value : imei)));
    }

    function updateSerialNumber(index: number, value: string) {
        setSerialNumbers((current) => current.map((serialNumber, i) => (i === index ? value : serialNumber)));
    }

    function addImeiField() {
        setImeis((current) => [...current, '']);
        setSerialNumbers((current) => [...current, '']);
    }

    function removeImeiField(index: number) {
        setImeis((current) => current.filter((_, i) => i !== index));
        setSerialNumbers((current) => current.filter((_, i) => i !== index));
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

    function updateVariantImei(variantIndex: number, imeiIndex: number, value: string) {
        setVariants((current) =>
            current.map((row, i) =>
                i === variantIndex
                    ? { ...row, initial_imeis: row.initial_imeis.map((imei, j) => (j === imeiIndex ? value : imei)) }
                    : row,
            ),
        );
    }

    function updateVariantSerialNumber(variantIndex: number, imeiIndex: number, value: string) {
        setVariants((current) =>
            current.map((row, i) =>
                i === variantIndex
                    ? { ...row, initial_serial_numbers: row.initial_serial_numbers.map((serialNumber, j) => (j === imeiIndex ? value : serialNumber)) }
                    : row,
            ),
        );
    }

    function addVariantImeiField(variantIndex: number) {
        setVariants((current) =>
            current.map((row, i) =>
                i === variantIndex
                    ? { ...row, initial_imeis: [...row.initial_imeis, ''], initial_serial_numbers: [...row.initial_serial_numbers, ''] }
                    : row,
            ),
        );
    }

    function removeVariantImeiField(variantIndex: number, imeiIndex: number) {
        setVariants((current) =>
            current.map((row, i) =>
                i === variantIndex
                    ? {
                          ...row,
                          initial_imeis: row.initial_imeis.filter((_, j) => j !== imeiIndex),
                          initial_serial_numbers: row.initial_serial_numbers.filter((_, j) => j !== imeiIndex),
                      }
                    : row,
            ),
        );
    }

    function submit(e: FormEvent) {
        e.preventDefault();

        transform((formData) => ({
            brand: formData.brand,
            model: formData.model,
            category: formData.category,
            unit: formData.unit,
            purpose: formData.purpose,
            supplier_id: formData.supplier_id ? parseInt(formData.supplier_id, 10) : null,
            product_image: formData.product_image,
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
            initial_serial_numbers: formData.is_serialized ? serialNumbers.map((serialNumber) => serialNumber.trim()) : null,
            attributes: serializeAttributes(attributes),
            is_commissionable: formData.is_commissionable,
            commission_amount_minor: formData.is_commissionable && formData.commission_amount_naira
                ? Math.round(parseFloat(formData.commission_amount_naira) * 100)
                : null,
        }));

        post('/admin/inventory/products', { forceFormData: true });
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
                unit: data.unit,
                purpose: data.purpose,
                supplier_id: data.supplier_id ? parseInt(data.supplier_id, 10) : null,
                product_image: data.product_image,
                condition: data.condition,
                is_serialized: data.is_serialized,
                shop_id: data.shop_id,
                variants: variants.map((row) => ({
                    attributes: serializeAttributes(row.attributes),
                    cost_price_minor: Math.round(parseFloat(row.cost_price_naira || '0') * 100),
                    markup_percent: parseFloat(row.markup_percent || '0'),
                    selling_price_minor_override: row.selling_price_override_naira
                        ? Math.round(parseFloat(row.selling_price_override_naira) * 100)
                        : null,
                    low_stock_threshold: row.low_stock_threshold ? parseInt(row.low_stock_threshold, 10) : null,
                    initial_quantity: !data.is_serialized && row.initial_quantity ? parseInt(row.initial_quantity, 10) : null,
                    initial_imeis: data.is_serialized ? row.initial_imeis.map((imei) => imei.trim()).filter(Boolean) : null,
                    initial_serial_numbers: data.is_serialized ? row.initial_serial_numbers.map((serialNumber) => serialNumber.trim()) : null,
                    is_commissionable: row.is_commissionable,
                    commission_amount_minor: row.is_commissionable && row.commission_amount_naira
                        ? Math.round(parseFloat(row.commission_amount_naira) * 100)
                        : null,
                })),
            },
            {
                forceFormData: true,
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
                    <MasterSelect
                        kind="category"
                        label="Category"
                        options={categories}
                        value={data.category}
                        onChange={(value) => setData('category', value)}
                        error={errors.category}
                        required
                    />
                    <MasterSelect
                        kind="unit"
                        label="Base unit"
                        options={units}
                        value={data.unit}
                        onChange={(value) => setData('unit', value)}
                        error={errors.unit}
                        required
                    />
                    <AdminSelect label="Used for" value={data.purpose} onChange={(e) => setData('purpose', e.target.value)} error={errors.purpose}>
                        {PURPOSES.map((p) => (
                            <option key={p.value} value={p.value}>
                                {p.label}
                            </option>
                        ))}
                    </AdminSelect>
                    <MasterSelect
                        kind="supplier"
                        label="Supplier"
                        options={suppliers}
                        value={data.supplier_id}
                        onChange={(value) => setData('supplier_id', value)}
                        error={errors.supplier_id}
                        allowEmpty
                    />
                    <AdminSelect label="Condition" value={data.condition} onChange={(e) => setData('condition', e.target.value)}>
                        {CONDITIONS.map((c) => (
                            <option key={c.value} value={c.value}>
                                {c.label}
                            </option>
                        ))}
                    </AdminSelect>
                    <div className="mb-5">
                        <label className="block text-xs font-semibold text-admin-text mb-1.5">Product image (optional)</label>
                        <input
                            type="file"
                            accept="image/*"
                            onChange={(e) => setData('product_image', e.target.files?.[0] ?? null)}
                            className="block w-full text-sm text-admin-text file:mr-3 file:rounded-admin-button file:border file:border-admin-border-strong file:bg-admin-surface file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-admin-text"
                        />
                        {errors.product_image && <p className="text-xs text-admin-red mt-1">{errors.product_image}</p>}
                    </div>
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
                                onChange={(e) => setData('is_serialized', e.target.checked)}
                            />
                            Serialized (tracked by IMEI/serial number)
                        </label>
                        <label className="flex items-center gap-2 text-sm text-admin-text">
                            <input type="checkbox" checked={hasVariants} onChange={(e) => setHasVariants(e.target.checked)} />
                            This product has variants (e.g. colors, storage sizes, compatibility)
                        </label>
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
                                label="Markup (%) optional"
                                type="number"
                                step="0.01"
                                value={data.markup_percent}
                                onChange={(e) => setData('markup_percent', e.target.value)}
                                error={errors.markup_percent}
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

                            <div className="mb-5 flex flex-col gap-2">
                                <label className="flex items-center gap-2 text-sm text-admin-text">
                                    <input
                                        type="checkbox"
                                        checked={data.is_commissionable}
                                        onChange={(e) => setData('is_commissionable', e.target.checked)}
                                    />
                                    Commissionable (staff earns a flat commission when this sells)
                                </label>
                                {data.is_commissionable && (
                                    <AdminInput
                                        label="Commission amount (₦)"
                                        type="number"
                                        step="0.01"
                                        value={data.commission_amount_naira}
                                        onChange={(e) => setData('commission_amount_naira', e.target.value)}
                                        error={errors.commission_amount_minor}
                                    />
                                )}
                            </div>

                            {data.is_serialized ? (
                                <div className="mb-6">
                                    <span className="block text-sm font-semibold text-admin-text mb-2">Initial IMEIs</span>
                                    <div className="flex flex-col gap-2">
                                        {imeis.map((imei, index) => (
                                            <div key={index} className="grid gap-2 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto]">
                                                <input
                                                    type="text"
                                                    value={imei}
                                                    onChange={(e) => updateImei(index, e.target.value)}
                                                    placeholder="15-digit IMEI"
                                                    className="border border-admin-border-strong rounded-admin-button px-admin-field-x py-admin-field-y text-admin-field"
                                                />
                                                <input
                                                    type="text"
                                                    value={serialNumbers[index] ?? ''}
                                                    onChange={(e) => updateSerialNumber(index, e.target.value)}
                                                    placeholder="Serial number (optional)"
                                                    className="border border-admin-border-strong rounded-admin-button px-admin-field-x py-admin-field-y text-admin-field"
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
                                            placeholder="Markup (%) optional"
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

                                    <div className="flex flex-col gap-1.5 mb-2">
                                        <label className="flex items-center gap-2 text-xs text-admin-text">
                                            <input
                                                type="checkbox"
                                                checked={row.is_commissionable}
                                                onChange={(e) => updateVariantRow(index, { is_commissionable: e.target.checked })}
                                            />
                                            Commissionable
                                        </label>
                                        {row.is_commissionable && (
                                            <input
                                                type="number"
                                                step="0.01"
                                                placeholder="Commission amount (₦)"
                                                value={row.commission_amount_naira}
                                                onChange={(e) => updateVariantRow(index, { commission_amount_naira: e.target.value })}
                                                className="border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs"
                                            />
                                        )}
                                    </div>

                                    {data.is_serialized ? (
                                        <div className="flex flex-col gap-2">
                                            <span className="text-xs font-semibold text-admin-text">Initial IMEIs for this variant</span>
                                            {row.initial_imeis.map((imei, imeiIndex) => (
                                                <div key={imeiIndex} className="grid gap-2 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto]">
                                                    <input
                                                        type="text"
                                                        value={imei}
                                                        onChange={(e) => updateVariantImei(index, imeiIndex, e.target.value)}
                                                        placeholder="15-digit IMEI"
                                                        className="border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs"
                                                    />
                                                    <input
                                                        type="text"
                                                        value={row.initial_serial_numbers[imeiIndex] ?? ''}
                                                        onChange={(e) => updateVariantSerialNumber(index, imeiIndex, e.target.value)}
                                                        placeholder="Serial number (optional)"
                                                        className="border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs"
                                                    />
                                                    {row.initial_imeis.length > 1 && (
                                                        <button
                                                            type="button"
                                                            onClick={() => removeVariantImeiField(index, imeiIndex)}
                                                            className="text-admin-red text-xs underline shrink-0"
                                                        >
                                                            Remove
                                                        </button>
                                                    )}
                                                </div>
                                            ))}
                                            <AdminButton type="button" variant="neutral" onClick={() => addVariantImeiField(index)}>
                                                Add another IMEI
                                            </AdminButton>
                                        </div>
                                    ) : (
                                        <input
                                            type="number"
                                            placeholder="Initial quantity"
                                            value={row.initial_quantity}
                                            onChange={(e) => updateVariantRow(index, { initial_quantity: e.target.value })}
                                            className="w-full border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs"
                                        />
                                    )}
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
