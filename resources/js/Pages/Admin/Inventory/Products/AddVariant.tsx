import { FormEvent, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AdminShell from '../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../Components/Admin/AdminPageHead';
import AdminInput from '../../../../Components/Admin/Forms/AdminInput';
import AdminSelect from '../../../../Components/Admin/Forms/AdminSelect';
import AdminButton from '../../../../Components/Admin/AdminButton';
import AttributeEditor, { emptyAttributes, serializeAttributes, VariantAttributes } from '../../../../Features/Inventory/AttributeEditor';

type Shop = { id: number; name: string; sku_prefix_code: string };
type Product = { id: number; brand: string; model: string; category: string };

/** INV-BR-18: adds a new non-serialized variant to an existing Product — reached from Products/Show's "Add variant" action. */
export default function AddVariant({ product, shops, isSerialized }: { product: Product; shops: Shop[]; isSerialized: boolean }) {
    const { data, setData, post, transform, processing, errors: formErrors } = useForm({
        shop_id: shops[0]?.id.toString() ?? '',
        cost_price_naira: '',
        markup_percent: '',
        selling_price_override_naira: '',
        low_stock_threshold: '',
        initial_quantity: '',
        is_commissionable: false,
        commission_amount_naira: '',
    });
    // The server validates minor-unit field names (`cost_price_minor`,
    // `commission_amount_minor`) that don't match this form's naira-bound field names.
    const errors = formErrors as Record<string, string | undefined>;
    const [attributes, setAttributes] = useState<VariantAttributes>(emptyAttributes());
    const [imeis, setImeis] = useState<string[]>(['']);
    const [serialNumbers, setSerialNumbers] = useState<string[]>(['']);

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

    function submit(e: FormEvent) {
        e.preventDefault();

        transform((formData) => ({
            shop_id: parseInt(formData.shop_id, 10),
            is_serialized: isSerialized,
            cost_price_minor: Math.round(parseFloat(formData.cost_price_naira || '0') * 100),
            markup_percent: parseFloat(formData.markup_percent || '0'),
            selling_price_minor_override: formData.selling_price_override_naira
                ? Math.round(parseFloat(formData.selling_price_override_naira) * 100)
                : null,
            low_stock_threshold: formData.low_stock_threshold ? parseInt(formData.low_stock_threshold, 10) : null,
            initial_quantity: !isSerialized && formData.initial_quantity ? parseInt(formData.initial_quantity, 10) : null,
            initial_imeis: isSerialized ? imeis.map((imei) => imei.trim()).filter(Boolean) : null,
            initial_serial_numbers: isSerialized ? serialNumbers.map((serialNumber) => serialNumber.trim()) : null,
            attributes: serializeAttributes(attributes),
            is_commissionable: formData.is_commissionable,
            commission_amount_minor: formData.is_commissionable && formData.commission_amount_naira
                ? Math.round(parseFloat(formData.commission_amount_naira) * 100)
                : null,
        }));

        post(`/admin/inventory/products/${product.id}/variants`);
    }

    return (
        <AdminShell>
            <Head title={`Add variant — ${product.brand} ${product.model}`} />

            <AdminPageHead
                title={`Add variant — ${product.brand} ${product.model}`}
                description={product.category}
                backHref={`/admin/inventory/products`}
                backLabel="Back to products"
            />

            <form onSubmit={submit} className="max-w-2xl">
                <div className="mb-5">
                    <span className="block text-xs font-semibold text-admin-text mb-1.5">Attributes</span>
                    <AttributeEditor value={attributes} onChange={setAttributes} />
                </div>

                <AdminSelect label="Shop" value={data.shop_id} onChange={(e) => setData('shop_id', e.target.value)} error={errors.shop_id}>
                    {shops.map((shop) => (
                        <option key={shop.id} value={shop.id}>
                            {shop.name} ({shop.sku_prefix_code})
                        </option>
                    ))}
                </AdminSelect>

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

                {isSerialized ? (
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
                                        <button type="button" onClick={() => removeImeiField(index)} className="text-admin-red text-xs underline shrink-0">
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
                        Add variant
                    </AdminButton>
                    <AdminButton type="button" variant="neutral" onClick={() => router.visit('/admin/inventory/products')}>
                        Cancel
                    </AdminButton>
                </div>
            </form>
        </AdminShell>
    );
}
