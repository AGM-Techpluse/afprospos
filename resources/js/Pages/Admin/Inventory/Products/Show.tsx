import { FormEvent, useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AdminShell from '../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../Components/Admin/AdminPageHead';
import AdminBadge from '../../../../Components/Admin/AdminBadge';
import AdminButton from '../../../../Components/Admin/AdminButton';

type StockByShop = { shop_id: number; on_hand: number; reserved: number; available: number };

type Sku = {
    id: number;
    sku_code: string;
    brand: string;
    model: string;
    category: string;
    is_serialized: boolean;
    cost_price_minor: number;
    markup_percent: number;
    selling_price_minor: number;
    selling_price_overridden: boolean;
    low_stock_threshold: number | null;
    stock_by_shop: StockByShop[];
};

function formatNaira(minor: number): string {
    return `₦${(minor / 100).toLocaleString('en-NG', { minimumFractionDigits: 0 })}`;
}

type Shop = { id: number; name: string; sku_prefix_code: string };

export default function ProductShow({ sku, shops }: { sku: Sku; shops: Shop[] }) {
    const { data, setData, post, transform, processing, errors, reset } = useForm({
        shop_id: shops[0]?.id.toString() ?? '',
        condition: 'new',
        quantity: '',
        imei: '',
    });

    function submitReceive(e: FormEvent) {
        e.preventDefault();

        transform((formData) => ({
            shop_id: parseInt(formData.shop_id, 10),
            condition: formData.condition,
            quantity: sku.is_serialized ? null : formData.quantity ? parseInt(formData.quantity, 10) : null,
            imeis: sku.is_serialized && formData.imei.trim() ? [formData.imei.trim()] : null,
        }));

        post(`/admin/inventory/products/${sku.id}/receive`, {
            onSuccess: () => reset('quantity', 'imei'),
        });
    }

    return (
        <AdminShell>
            <Head title={`${sku.brand} ${sku.model}`} />

            <AdminPageHead
                title={`${sku.brand} ${sku.model}`}
                description={sku.sku_code}
                backHref="/admin/inventory/products"
                backLabel="Back to products"
            />

            <div className="grid gap-6 sm:grid-cols-2">
                <div className="bg-admin-surface border border-admin-border rounded-admin-card p-6">
                    <h2 className="text-admin-md font-semibold text-admin-text mb-4">Details</h2>
                    <dl className="space-y-2 text-sm">
                        <div className="flex justify-between">
                            <dt className="text-admin-text2">Category</dt>
                            <dd className="text-admin-text">{sku.category}</dd>
                        </div>
                        <div className="flex justify-between">
                            <dt className="text-admin-text2">Type</dt>
                            <dd>
                                <AdminBadge status={sku.is_serialized ? 'info' : 'neutral'}>
                                    {sku.is_serialized ? 'Serialized' : 'Bulk'}
                                </AdminBadge>
                            </dd>
                        </div>
                        <div className="flex justify-between">
                            <dt className="text-admin-text2">Cost price</dt>
                            <dd className="text-admin-text">{formatNaira(sku.cost_price_minor)}</dd>
                        </div>
                        <div className="flex justify-between">
                            <dt className="text-admin-text2">Markup</dt>
                            <dd className="text-admin-text">{sku.markup_percent}%</dd>
                        </div>
                        <div className="flex justify-between">
                            <dt className="text-admin-text2">Selling price</dt>
                            <dd className="text-admin-text">
                                {formatNaira(sku.selling_price_minor)}
                                {sku.selling_price_overridden && <span className="text-admin-text3"> (override)</span>}
                            </dd>
                        </div>
                        <div className="flex justify-between">
                            <dt className="text-admin-text2">Low stock threshold</dt>
                            <dd className="text-admin-text">{sku.low_stock_threshold ?? '—'}</dd>
                        </div>
                    </dl>
                </div>

                <div className="bg-admin-surface border border-admin-border rounded-admin-card p-6">
                    <h2 className="text-admin-md font-semibold text-admin-text mb-4">Stock by shop</h2>
                    {sku.stock_by_shop.length === 0 ? (
                        <p className="text-admin-text2 text-sm">
                            {sku.is_serialized
                                ? 'No stock breakdown for serialized units here — see the Stock page for individual units.'
                                : 'No stock recorded at any shop yet.'}
                        </p>
                    ) : (
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="text-left text-admin-text2 text-xs">
                                    <th className="pb-2">Shop</th>
                                    <th className="pb-2 text-right">On hand</th>
                                    <th className="pb-2 text-right">Reserved</th>
                                    <th className="pb-2 text-right">Available</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-admin-border">
                                {sku.stock_by_shop.map((row) => (
                                    <tr key={row.shop_id}>
                                        <td className="py-2 text-admin-text">Shop #{row.shop_id}</td>
                                        <td className="py-2 text-right text-admin-text">{row.on_hand}</td>
                                        <td className="py-2 text-right text-admin-text">{row.reserved}</td>
                                        <td className="py-2 text-right text-admin-text font-semibold">{row.available}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>
            </div>

            <div className="mt-6 max-w-lg bg-admin-surface border border-admin-border rounded-admin-card p-6">
                <h2 className="text-admin-md font-semibold text-admin-text mb-4">Receive stock</h2>
                <form onSubmit={submitReceive}>
                    <div className="mb-4">
                        <label className="block text-xs font-semibold text-admin-text mb-1.5">Shop</label>
                        <select
                            value={data.shop_id}
                            onChange={(e) => setData('shop_id', e.target.value)}
                            className="w-full border border-admin-border-strong rounded-admin-button px-admin-field-x py-admin-field-y text-admin-field"
                        >
                            {shops.map((shop) => (
                                <option key={shop.id} value={shop.id}>
                                    {shop.name} ({shop.sku_prefix_code})
                                </option>
                            ))}
                        </select>
                    </div>

                    {sku.is_serialized ? (
                        <div className="mb-4">
                            <label className="block text-xs font-semibold text-admin-text mb-1.5">IMEI</label>
                            <input
                                type="text"
                                value={data.imei}
                                onChange={(e) => setData('imei', e.target.value)}
                                placeholder="15-digit IMEI"
                                className="w-full border border-admin-border-strong rounded-admin-button px-admin-field-x py-admin-field-y text-admin-field"
                            />
                            {errors.imeis && <p className="text-xs text-admin-red mt-1">{errors.imeis}</p>}
                        </div>
                    ) : (
                        <div className="mb-4">
                            <label className="block text-xs font-semibold text-admin-text mb-1.5">Quantity</label>
                            <input
                                type="number"
                                value={data.quantity}
                                onChange={(e) => setData('quantity', e.target.value)}
                                className="w-full border border-admin-border-strong rounded-admin-button px-admin-field-x py-admin-field-y text-admin-field"
                            />
                        </div>
                    )}

                    <AdminButton type="submit" isLoading={processing}>
                        Receive stock
                    </AdminButton>
                </form>
            </div>
        </AdminShell>
    );
}
