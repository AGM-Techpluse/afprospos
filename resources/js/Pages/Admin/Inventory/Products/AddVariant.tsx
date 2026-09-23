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
export default function AddVariant({ product, shops }: { product: Product; shops: Shop[] }) {
    const { data, setData, post, transform, processing, errors } = useForm({
        shop_id: shops[0]?.id.toString() ?? '',
        cost_price_naira: '',
        markup_percent: '20',
        selling_price_override_naira: '',
        low_stock_threshold: '',
        initial_quantity: '',
    });
    const [attributes, setAttributes] = useState<VariantAttributes>(emptyAttributes());

    function submit(e: FormEvent) {
        e.preventDefault();

        transform((formData) => ({
            shop_id: parseInt(formData.shop_id, 10),
            cost_price_minor: Math.round(parseFloat(formData.cost_price_naira || '0') * 100),
            markup_percent: parseFloat(formData.markup_percent || '0'),
            selling_price_minor_override: formData.selling_price_override_naira
                ? Math.round(parseFloat(formData.selling_price_override_naira) * 100)
                : null,
            low_stock_threshold: formData.low_stock_threshold ? parseInt(formData.low_stock_threshold, 10) : null,
            initial_quantity: formData.initial_quantity ? parseInt(formData.initial_quantity, 10) : null,
            attributes: serializeAttributes(attributes),
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
                <AdminInput
                    label="Initial quantity"
                    type="number"
                    value={data.initial_quantity}
                    onChange={(e) => setData('initial_quantity', e.target.value)}
                />

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
