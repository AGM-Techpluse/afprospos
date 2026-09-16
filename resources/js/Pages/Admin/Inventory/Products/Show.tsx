import { FormEvent, useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AdminShell from '../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../Components/Admin/AdminPageHead';
import AdminBadge, { AdminBadgeStatus } from '../../../../Components/Admin/AdminBadge';
import AdminButton from '../../../../Components/Admin/AdminButton';
import AdminInfoCard from '../../../../Components/Admin/AdminInfoCard';
import AdminStatCard from '../../../../Components/Admin/AdminStatCard';

type StockByShop = { shop_id: number; on_hand: number; reserved: number; available: number };
type SerializedUnit = { id: number; imei: string; shop_id: number; condition: string; status: string };

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
    serialized_units: SerializedUnit[];
    on_hand: number;
    reserved: number;
    available: number;
};

function formatNaira(minor: number): string {
    return `₦${(minor / 100).toLocaleString('en-NG', { minimumFractionDigits: 0 })}`;
}

const UNIT_STATUS_BADGE: Record<string, AdminBadgeStatus> = {
    available: 'success',
    reserved: 'warning',
    transferring: 'info',
    sold: 'neutral',
};

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

            {/* UI/UX §14C.8's headline stat row — the total across every shop, so "how many of this do we have" has one clear answer regardless of serialized vs. bulk. Per-shop detail is still below, for the breakdown. */}
            <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                <AdminStatCard label="On hand" value={String(sku.on_hand)} />
                <AdminStatCard label="Reserved" value={String(sku.reserved)} />
                <AdminStatCard label="Available" value={String(sku.available)} />
                <AdminStatCard label="Low-stock threshold" value={sku.low_stock_threshold !== null ? String(sku.low_stock_threshold) : '—'} />
            </div>

            <div className="grid gap-6 sm:grid-cols-2">
                <AdminInfoCard title="Details" icon="info-circle" iconColor="blue">
                    <dl className="divide-y divide-admin-border -my-1 text-sm">
                        <div className="flex justify-between py-2.5">
                            <dt className="text-admin-text2">Category</dt>
                            <dd className="text-admin-text">{sku.category}</dd>
                        </div>
                        <div className="flex justify-between py-2.5">
                            <dt className="text-admin-text2">Type</dt>
                            <dd>
                                <AdminBadge status={sku.is_serialized ? 'info' : 'neutral'}>
                                    {sku.is_serialized ? 'Serialized' : 'Bulk'}
                                </AdminBadge>
                            </dd>
                        </div>
                        <div className="flex justify-between py-2.5">
                            <dt className="text-admin-text2">Cost price</dt>
                            <dd className="text-admin-text">{formatNaira(sku.cost_price_minor)}</dd>
                        </div>
                        <div className="flex justify-between py-2.5">
                            <dt className="text-admin-text2">Markup</dt>
                            <dd className="text-admin-text">{sku.markup_percent}%</dd>
                        </div>
                        <div className="flex justify-between py-2.5">
                            <dt className="text-admin-text2">Selling price</dt>
                            <dd className="text-admin-text">
                                {formatNaira(sku.selling_price_minor)}
                                {sku.selling_price_overridden && <span className="text-admin-text3"> (override)</span>}
                            </dd>
                        </div>
                        <div className="flex justify-between py-2.5">
                            <dt className="text-admin-text2">Low stock threshold</dt>
                            <dd className="text-admin-text">{sku.low_stock_threshold ?? '—'}</dd>
                        </div>
                    </dl>
                </AdminInfoCard>

                <AdminInfoCard title={sku.is_serialized ? 'Units by shop' : 'Stock by shop'} icon="shop" iconColor="green">
                    {sku.is_serialized ? (
                        sku.serialized_units.length === 0 ? (
                            <p className="text-admin-text2 text-sm">No units received yet.</p>
                        ) : (
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="text-left text-admin-text2 text-xs">
                                        <th className="pb-2">IMEI</th>
                                        <th className="pb-2">Shop</th>
                                        <th className="pb-2">Condition</th>
                                        <th className="pb-2 text-right">Status</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-admin-border">
                                    {sku.serialized_units.map((unit) => (
                                        <tr key={unit.id}>
                                            <td className="py-2 text-admin-text font-mono text-xs">{unit.imei}</td>
                                            <td className="py-2 text-admin-text">{shops.find((shop) => shop.id === unit.shop_id)?.name ?? 'Unknown shop'}</td>
                                            <td className="py-2 text-admin-text capitalize">{unit.condition}</td>
                                            <td className="py-2 text-right">
                                                <AdminBadge status={UNIT_STATUS_BADGE[unit.status] ?? 'neutral'}>{unit.status}</AdminBadge>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )
                    ) : sku.stock_by_shop.length === 0 ? (
                        <p className="text-admin-text2 text-sm">No stock recorded at any shop yet.</p>
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
                                        <td className="py-2 text-admin-text">{shops.find((shop) => shop.id === row.shop_id)?.name ?? 'Unknown shop'}</td>
                                        <td className="py-2 text-right text-admin-text">{row.on_hand}</td>
                                        <td className="py-2 text-right text-admin-text">{row.reserved}</td>
                                        <td className="py-2 text-right text-admin-text font-semibold">{row.available}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </AdminInfoCard>
            </div>

            {/* CLAUDE.md Invariant #19: a <form> never sits inside a decorative card — plain heading directly on the page background. */}
            <div className="mt-8 max-w-lg">
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
