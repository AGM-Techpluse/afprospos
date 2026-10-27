import { FormEvent, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AdminShell from '../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../Components/Admin/AdminPageHead';
import AdminBadge, { AdminBadgeStatus } from '../../../../Components/Admin/AdminBadge';
import AdminButton from '../../../../Components/Admin/AdminButton';
import AdminInfoCard from '../../../../Components/Admin/AdminInfoCard';
import AdminSelect from '../../../../Components/Admin/Forms/AdminSelect';
import AdminStatCard from '../../../../Components/Admin/AdminStatCard';
import ProductImage from '../../../../Components/ProductImage';
import AttributeEditor, { serializeAttributes, VariantAttributes } from '../../../../Features/Inventory/AttributeEditor';

type StockByShop = { shop_id: number; on_hand: number; reserved: number; available: number };
type SerializedUnit = { id: number; imei: string; serial_number: string | null; shop_id: number; condition: string; status: string };
type SiblingSku = { id: number; sku_code: string; attributes: Record<string, unknown>; selling_price_minor: number };

type Sku = {
    id: number;
    product_id: number;
    sku_code: string;
    brand: string;
    model: string;
    category: string;
    image_url: string | null;
    description: string | null;
    attributes: Record<string, unknown>;
    is_serialized: boolean;
    cost_price_minor: number;
    markup_percent: number;
    selling_price_minor: number;
    selling_price_overridden: boolean;
    low_stock_threshold: number | null;
    is_commissionable: boolean;
    commission_amount_minor: number | null;
    stock_by_shop: StockByShop[];
    serialized_units: SerializedUnit[];
    sibling_skus: SiblingSku[];
    on_hand: number;
    reserved: number;
    available: number;
};

function variantLabel(attributes: Record<string, unknown>): string {
    const parts = ['brand', 'color', 'storage', 'material', 'compatibility']
        .map((key) => attributes[key])
        .filter((value): value is string => typeof value === 'string' && value !== '');

    return parts.length > 0 ? parts.join(' · ') : 'No attributes set';
}

function formatNaira(minor: number): string {
    return `₦${(minor / 100).toLocaleString('en-NG', { minimumFractionDigits: 0 })}`;
}

const UNIT_STATUS_BADGE: Record<string, AdminBadgeStatus> = {
    available: 'success',
    reserved: 'warning',
    transferring: 'info',
    sold: 'neutral',
    damaged: 'danger',
};

/** Turns the backend's {brand,color,storage,material,compatibility,custom:{key:value}} shape into AttributeEditor's row-based editing shape. */
function toVariantAttributes(attributes: Record<string, unknown>): VariantAttributes {
    const custom = attributes.custom;
    const customRows = custom && typeof custom === 'object'
        ? Object.entries(custom as Record<string, unknown>).map(([key, value]) => ({ key, value: String(value) }))
        : [];

    return {
        brand: typeof attributes.brand === 'string' ? attributes.brand : '',
        color: typeof attributes.color === 'string' ? attributes.color : '',
        storage: typeof attributes.storage === 'string' ? attributes.storage : '',
        material: typeof attributes.material === 'string' ? attributes.material : '',
        compatibility: typeof attributes.compatibility === 'string' ? attributes.compatibility : '',
        custom: customRows,
    };
}

type Shop = { id: number; name: string; sku_prefix_code: string };

export default function ProductShow({ sku, shops }: { sku: Sku; shops: Shop[] }) {
    const { data, setData, post, transform, processing, errors: formErrors, reset } = useForm({
        shop_id: shops[0]?.id.toString() ?? '',
        condition: 'new',
        quantity: '',
        imei: '',
        serial_number: '',
    });
    // The server validates plural field names (`imeis`, `serial_numbers`) that
    // don't match this form's singular-bound field names.
    const errors = formErrors as Record<string, string | undefined>;

    const commissionForm = useForm({
        is_commissionable: sku.is_commissionable,
        commission_amount_naira: sku.commission_amount_minor !== null ? String(sku.commission_amount_minor / 100) : '',
    });
    // The server validates the minor-unit field (`commission_amount_minor`) that
    // doesn't match this form's naira-bound field name.
    const commissionErrors = commissionForm.errors as Record<string, string | undefined>;

    function submitCommission(e: FormEvent) {
        e.preventDefault();

        commissionForm.transform((formData) => ({
            is_commissionable: formData.is_commissionable,
            commission_amount_minor: formData.is_commissionable && formData.commission_amount_naira
                ? Math.round(parseFloat(formData.commission_amount_naira) * 100)
                : null,
        }));

        commissionForm.put(`/admin/inventory/products/${sku.id}/commission`);
    }

    const [editingDescription, setEditingDescription] = useState(false);
    const descriptionForm = useForm({ description: sku.description ?? '' });

    const [updatingUnitId, setUpdatingUnitId] = useState<number | null>(null);

    function updateUnitStatus(unitId: number, toStatus: 'damaged' | 'available') {
        setUpdatingUnitId(unitId);
        router.post(
            `/admin/inventory/serialized-units/${unitId}/status`,
            { to_status: toStatus },
            { preserveScroll: true, onFinish: () => setUpdatingUnitId(null) },
        );
    }

    const [editingAttributes, setEditingAttributes] = useState(false);
    const attributesForm = useForm<{ attributes: VariantAttributes }>({ attributes: toVariantAttributes(sku.attributes) });
    const attributesErrors = attributesForm.errors as Record<string, string | undefined>;

    function submitAttributes(e: FormEvent) {
        e.preventDefault();
        attributesForm.transform((formData) => ({ attributes: serializeAttributes(formData.attributes) }));
        attributesForm.put(`/admin/inventory/products/${sku.id}/attributes`, {
            preserveScroll: true,
            onSuccess: () => setEditingAttributes(false),
        });
    }

    function submitDescription(e: FormEvent) {
        e.preventDefault();
        descriptionForm.put(`/admin/inventory/products/${sku.id}/description`, {
            preserveScroll: true,
            onSuccess: () => setEditingDescription(false),
        });
    }

    function submitReceive(e: FormEvent) {
        e.preventDefault();

        transform((formData) => ({
            shop_id: parseInt(formData.shop_id, 10),
            condition: formData.condition,
            quantity: sku.is_serialized ? null : formData.quantity ? parseInt(formData.quantity, 10) : null,
            imeis: sku.is_serialized && formData.imei.trim() ? [formData.imei.trim()] : null,
            serial_numbers: sku.is_serialized && formData.serial_number.trim() ? [formData.serial_number.trim()] : null,
        }));

        post(`/admin/inventory/products/${sku.id}/receive`, {
            onSuccess: () => reset('quantity', 'imei', 'serial_number'),
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

            <div className="mb-6 grid gap-4 sm:grid-cols-[200px_minmax(0,1fr)] items-start">
                <ProductImage src={sku.image_url} alt={`${sku.brand} ${sku.model}`} aspect="square" category={sku.category} className="max-w-[200px]" />

                <div>
                    <div className="flex items-center justify-between gap-2 mb-1.5">
                        <span className="text-xs font-semibold text-admin-text2">Storefront description</span>
                        {!editingDescription && (
                            <button
                                type="button"
                                onClick={() => setEditingDescription(true)}
                                className="text-xs font-semibold text-admin-blue"
                            >
                                {sku.description ? 'Edit' : 'Add description'}
                            </button>
                        )}
                    </div>

                    {editingDescription ? (
                        <form onSubmit={submitDescription} className="flex flex-col gap-2">
                            <textarea
                                value={descriptionForm.data.description}
                                onChange={(e) => descriptionForm.setData('description', e.target.value)}
                                rows={4}
                                maxLength={2000}
                                placeholder="What a customer browsing the storefront should know about this product…"
                                className="border border-admin-border-strong rounded-admin-button px-2.5 py-1.5 text-sm outline-none focus:border-admin-blue focus:ring-2 focus:ring-admin-focus"
                            />
                            <div className="flex items-center gap-2">
                                <AdminButton type="submit" variant="neutral" isLoading={descriptionForm.processing}>Save</AdminButton>
                                <AdminButton
                                    type="button"
                                    variant="neutral"
                                    onClick={() => {
                                        descriptionForm.setData('description', sku.description ?? '');
                                        setEditingDescription(false);
                                    }}
                                >
                                    Cancel
                                </AdminButton>
                            </div>
                        </form>
                    ) : (
                        <p className="text-sm text-admin-text2 whitespace-pre-wrap">
                            {sku.description || 'No description yet — this product will show no copy on the storefront.'}
                        </p>
                    )}
                </div>
            </div>

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
                        <div className="flex justify-between py-2.5">
                            <dt className="text-admin-text2">Commission</dt>
                            <dd className="text-admin-text">
                                {sku.is_commissionable && sku.commission_amount_minor !== null
                                    ? `${formatNaira(sku.commission_amount_minor)} per sale`
                                    : 'Not commissionable'}
                            </dd>
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
                                        <th className="pb-2">Serial</th>
                                        <th className="pb-2">Shop</th>
                                        <th className="pb-2">Condition</th>
                                        <th className="pb-2 text-right">Status</th>
                                        <th className="pb-2 text-right"></th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-admin-border">
                                    {sku.serialized_units.map((unit) => (
                                        <tr key={unit.id}>
                                            <td className="py-2 text-admin-text font-mono text-xs">{unit.imei}</td>
                                            <td className="py-2 text-admin-text font-mono text-xs">{unit.serial_number ?? '-'}</td>
                                            <td className="py-2 text-admin-text">{shops.find((shop) => shop.id === unit.shop_id)?.name ?? 'Unknown shop'}</td>
                                            <td className="py-2 text-admin-text capitalize">{unit.condition}</td>
                                            <td className="py-2 text-right">
                                                <AdminBadge status={UNIT_STATUS_BADGE[unit.status] ?? 'neutral'}>{unit.status}</AdminBadge>
                                            </td>
                                            <td className="py-2 text-right">
                                                {unit.status === 'available' && (
                                                    <button
                                                        type="button"
                                                        disabled={updatingUnitId === unit.id}
                                                        onClick={() => updateUnitStatus(unit.id, 'damaged')}
                                                        className="text-xs font-semibold text-admin-red disabled:opacity-50"
                                                    >
                                                        Mark damaged
                                                    </button>
                                                )}
                                                {unit.status === 'damaged' && (
                                                    <button
                                                        type="button"
                                                        disabled={updatingUnitId === unit.id}
                                                        onClick={() => updateUnitStatus(unit.id, 'available')}
                                                        className="text-xs font-semibold text-admin-blue disabled:opacity-50"
                                                    >
                                                        Restore
                                                    </button>
                                                )}
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

            {(
                <div className="mt-8">
                    <AdminInfoCard title="Variants" icon="grid-3x3-gap" iconColor="blue">
                        <div className="mb-3">
                            <div className="flex items-center justify-between py-2.5 border-b border-admin-border">
                                <span className="text-sm text-admin-text">{variantLabel(sku.attributes)} (this variant)</span>
                                <span className="text-sm font-medium text-admin-text">{formatNaira(sku.selling_price_minor)}</span>
                            </div>
                            {sku.sibling_skus.length === 0 ? (
                                <p className="text-admin-text2 text-sm py-2.5">No other variants yet.</p>
                            ) : (
                                sku.sibling_skus.map((sibling) => (
                                    <button
                                        key={sibling.id}
                                        type="button"
                                        onClick={() => router.visit(`/admin/inventory/products/${sibling.id}`)}
                                        className="w-full flex items-center justify-between py-2.5 border-b border-admin-border last:border-b-0 text-left hover:bg-admin-hover"
                                    >
                                        <span className="text-sm text-admin-text">{variantLabel(sibling.attributes)}</span>
                                        <span className="text-sm font-medium text-admin-text">{formatNaira(sibling.selling_price_minor)}</span>
                                    </button>
                                ))
                            )}
                        </div>
                        <AdminButton
                            type="button"
                            variant="neutral"
                            onClick={() => router.visit(`/admin/inventory/products/${sku.product_id}/variants/create?serialized=${sku.is_serialized ? '1' : '0'}`)}
                        >
                            Add variant
                        </AdminButton>
                    </AdminInfoCard>
                </div>
            )}

            {/* CLAUDE.md Invariant #19: a <form> never sits inside a decorative card — plain heading directly on the page background. */}
            <div className="mt-8 max-w-lg">
                <div className="flex items-center justify-between mb-4">
                    <h2 className="text-admin-md font-semibold text-admin-text">Product specs</h2>
                    {!editingAttributes && (
                        <button type="button" onClick={() => setEditingAttributes(true)} className="text-xs font-semibold text-admin-blue">
                            Edit
                        </button>
                    )}
                </div>

                {editingAttributes ? (
                    <form onSubmit={submitAttributes}>
                        <AttributeEditor
                            value={attributesForm.data.attributes}
                            onChange={(value) => attributesForm.setData('attributes', value)}
                        />
                        {attributesErrors.attributes && <p className="text-xs text-admin-red mt-2">{attributesErrors.attributes}</p>}
                        <div className="flex items-center gap-2 mt-3">
                            <AdminButton type="submit" variant="neutral" isLoading={attributesForm.processing}>Save</AdminButton>
                            <AdminButton
                                type="button"
                                variant="neutral"
                                onClick={() => {
                                    attributesForm.setData('attributes', toVariantAttributes(sku.attributes));
                                    setEditingAttributes(false);
                                }}
                            >
                                Cancel
                            </AdminButton>
                        </div>
                    </form>
                ) : (
                    <p className="text-sm text-admin-text2">{variantLabel(sku.attributes)}</p>
                )}
            </div>

            {/* CLAUDE.md Invariant #19: a <form> never sits inside a decorative card — plain heading directly on the page background. */}
            <div className="mt-8 max-w-lg">
                <h2 className="text-admin-md font-semibold text-admin-text mb-4">Receive stock</h2>
                <form onSubmit={submitReceive}>
                    <AdminSelect label="Shop" value={data.shop_id} onChange={(e) => setData('shop_id', e.target.value)}>
                        {shops.map((shop) => (
                            <option key={shop.id} value={shop.id}>
                                {shop.name} ({shop.sku_prefix_code})
                            </option>
                        ))}
                    </AdminSelect>

                    {sku.is_serialized ? (
                        <div className="mb-4 grid gap-3 sm:grid-cols-2">
                            <div>
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
                            <div>
                                <label className="block text-xs font-semibold text-admin-text mb-1.5">Serial number (optional)</label>
                                <input
                                    type="text"
                                    value={data.serial_number}
                                    onChange={(e) => setData('serial_number', e.target.value)}
                                    placeholder="Serial number"
                                    className="w-full border border-admin-border-strong rounded-admin-button px-admin-field-x py-admin-field-y text-admin-field"
                                />
                                {errors.serial_numbers && <p className="text-xs text-admin-red mt-1">{errors.serial_numbers}</p>}
                            </div>
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

            {/* CLAUDE.md Invariant #19: a <form> never sits inside a decorative card — plain heading directly on the page background. */}
            <div className="mt-8 max-w-lg">
                <h2 className="text-admin-md font-semibold text-admin-text mb-4">Commission</h2>
                <form onSubmit={submitCommission}>
                    <label className="flex items-center gap-2 text-sm text-admin-text mb-4">
                        <input
                            type="checkbox"
                            checked={commissionForm.data.is_commissionable}
                            onChange={(e) => commissionForm.setData('is_commissionable', e.target.checked)}
                        />
                        Commissionable (staff earns a flat commission when this sells)
                    </label>
                    {commissionForm.data.is_commissionable && (
                        <div className="mb-4">
                            <label className="block text-xs font-semibold text-admin-text mb-1.5">Commission amount (₦)</label>
                            <input
                                type="number"
                                step="0.01"
                                value={commissionForm.data.commission_amount_naira}
                                onChange={(e) => commissionForm.setData('commission_amount_naira', e.target.value)}
                                className="w-full border border-admin-border-strong rounded-admin-button px-admin-field-x py-admin-field-y text-admin-field"
                            />
                            {commissionErrors.commission_amount_minor && (
                                <p className="text-xs text-admin-red mt-1">{commissionErrors.commission_amount_minor}</p>
                            )}
                        </div>
                    )}
                    <AdminButton type="submit" isLoading={commissionForm.processing}>
                        Save commission settings
                    </AdminButton>
                </form>
            </div>
        </AdminShell>
    );
}
