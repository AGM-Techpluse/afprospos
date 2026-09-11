import { FormEvent, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AdminShell from '../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../Components/Admin/AdminPageHead';
import AdminInput from '../../../../Components/Admin/Forms/AdminInput';
import AdminButton from '../../../../Components/Admin/AdminButton';

type Shop = { id: number; name: string; sku_prefix_code: string };

export default function TransferCreate({ shops }: { shops: Shop[] }) {
    const { data, setData, post, transform, processing, errors } = useForm({
        sku_id: '',
        inventory_item_id: '',
        quantity: '',
        from_shop_id: shops[0]?.id.toString() ?? '',
        to_shop_id: shops[1]?.id.toString() ?? shops[0]?.id.toString() ?? '',
    });
    const [isSerialized, setIsSerialized] = useState(true);

    function submit(e: FormEvent) {
        e.preventDefault();

        transform((formData) => ({
            sku_id: formData.sku_id,
            from_shop_id: formData.from_shop_id,
            to_shop_id: formData.to_shop_id,
            inventory_item_id: isSerialized ? formData.inventory_item_id : null,
            quantity: isSerialized ? null : formData.quantity,
        }));

        post('/admin/inventory/transfers');
    }

    return (
        <AdminShell>
            <Head title="New transfer" />

            <AdminPageHead
                title="New transfer"
                description="Move stock or a specific unit between shops."
                backHref="/admin/inventory/transfers"
                backLabel="Back to transfers"
            />

            <form onSubmit={submit} className="max-w-[640px]">
                <AdminInput
                    label="SKU ID"
                    type="number"
                    value={data.sku_id}
                    onChange={(e) => setData('sku_id', e.target.value)}
                    error={errors.sku_id}
                    required
                />

                <div className="mb-5">
                    <label className="flex items-center gap-2 text-sm text-admin-text">
                        <input type="checkbox" checked={isSerialized} onChange={(e) => setIsSerialized(e.target.checked)} />
                        Serialized (transfer one specific unit by its inventory item ID)
                    </label>
                </div>

                {isSerialized ? (
                    <AdminInput
                        label="Inventory item ID"
                        type="number"
                        value={data.inventory_item_id}
                        onChange={(e) => setData('inventory_item_id', e.target.value)}
                        error={errors.inventory_item_id}
                        required
                    />
                ) : (
                    <AdminInput
                        label="Quantity"
                        type="number"
                        value={data.quantity}
                        onChange={(e) => setData('quantity', e.target.value)}
                        error={errors.quantity}
                        required
                    />
                )}

                <div className="mb-4">
                    <label className="block text-xs font-semibold text-admin-text mb-1.5">From shop</label>
                    <select
                        value={data.from_shop_id}
                        onChange={(e) => setData('from_shop_id', e.target.value)}
                        className="w-full border border-admin-border-strong rounded-admin-button px-admin-field-x py-admin-field-y text-admin-field"
                    >
                        {shops.map((shop) => (
                            <option key={shop.id} value={shop.id}>
                                {shop.name} ({shop.sku_prefix_code})
                            </option>
                        ))}
                    </select>
                </div>

                <div className="mb-6">
                    <label className="block text-xs font-semibold text-admin-text mb-1.5">To shop</label>
                    <select
                        value={data.to_shop_id}
                        onChange={(e) => setData('to_shop_id', e.target.value)}
                        className="w-full border border-admin-border-strong rounded-admin-button px-admin-field-x py-admin-field-y text-admin-field"
                    >
                        {shops.map((shop) => (
                            <option key={shop.id} value={shop.id}>
                                {shop.name} ({shop.sku_prefix_code})
                            </option>
                        ))}
                    </select>
                    {errors.to_shop_id && <p className="text-xs text-admin-red mt-1">{errors.to_shop_id}</p>}
                </div>

                <div className="flex items-center gap-2">
                    <AdminButton type="submit" isLoading={processing}>
                        Initiate transfer
                    </AdminButton>
                    <AdminButton type="button" variant="neutral" onClick={() => router.visit('/admin/inventory/transfers')}>
                        Cancel
                    </AdminButton>
                </div>
            </form>
        </AdminShell>
    );
}
