import { FormEvent, useEffect, useRef, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AdminShell from '../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../Components/Admin/AdminPageHead';
import AdminBadge, { AdminBadgeStatus } from '../../../../Components/Admin/AdminBadge';
import AdminButton from '../../../../Components/Admin/AdminButton';
import AdminEmptyState from '../../../../Components/Admin/AdminEmptyState';
import AdminInfoCard from '../../../../Components/Admin/AdminInfoCard';
import AdminPagination from '../../../../Components/Admin/AdminPagination';
import AdminSelect from '../../../../Components/Admin/Forms/AdminSelect';
import AdminStatCard from '../../../../Components/Admin/AdminStatCard';
import ProductImage from '../../../../Components/ProductImage';
import Icon from '../../../../Components/Icons/Icon';
import ResponsiveDataTable, { DataTableColumn } from '../../../../Components/Tables/ResponsiveDataTable';
import { VariantAttributes } from '../../../../Features/Inventory/AttributeEditor';
import type { MasterEntry } from '../../../../Features/Inventory/MasterSelect';
import { formatInventoryCondition } from '../../../../lib/inventoryCondition';
import EditProductModal from './EditProductModal';
import EditUnitConditionModal from './EditUnitConditionModal';

type StockByShop = { shop_id: number; on_hand: number; reserved: number; available: number };
type SerializedUnit = {
    id: number;
    imei: string;
    serial_number: string | null;
    sku_id: number;
    shop_id: number;
    condition: string;
    status: string;
};
type Paginated<T> = { data: T[]; current_page: number; last_page: number; per_page: number; total: number };
type UnitFilters = { search: string; shop_id: number | null; condition: string; status: string };
type SiblingSku = { id: number; sku_code: string; attributes: Record<string, unknown>; selling_price_minor: number };

export type Sku = {
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
export function toVariantAttributes(attributes: Record<string, unknown>): VariantAttributes {
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

interface ProductShowProps {
    sku: Sku;
    shops: Shop[];
    conditions: MasterEntry[];
    units: Paginated<SerializedUnit> | null;
    unitFilters: UnitFilters;
}

export default function ProductShow({ sku, shops, conditions, units, unitFilters }: ProductShowProps) {
    const { data, setData, post, transform, processing, errors: formErrors, reset } = useForm({
        shop_id: shops[0]?.id.toString() ?? '',
        condition: conditions.find((condition) => condition.name === 'new')?.name ?? conditions[0]?.name ?? '',
        quantity: '',
        imei: '',
        serial_number: '',
    });
    // The server validates plural field names (`imeis`, `serial_numbers`) that
    // don't match this form's singular-bound field names.
    const errors = formErrors as Record<string, string | undefined>;

    const [updatingUnitId, setUpdatingUnitId] = useState<number | null>(null);

    function updateUnitStatus(unitId: number, toStatus: 'damaged' | 'available') {
        setUpdatingUnitId(unitId);
        router.post(
            `/admin/inventory/serialized-units/${unitId}/status`,
            { to_status: toStatus },
            { preserveScroll: true, onFinish: () => setUpdatingUnitId(null) },
        );
    }

    const [editingProduct, setEditingProduct] = useState(false);
    const [editingUnit, setEditingUnit] = useState<SerializedUnit | null>(null);

    const [unitSearch, setUnitSearch] = useState(unitFilters.search);
    const [unitShopId, setUnitShopId] = useState(unitFilters.shop_id);
    const [unitCondition, setUnitCondition] = useState(unitFilters.condition);
    const [unitStatus, setUnitStatus] = useState(unitFilters.status);
    const [unitsLoading, setUnitsLoading] = useState(false);
    const skipNextUnitsFetch = useRef(true);

    function unitQuery(extra: Record<string, unknown> = {}) {
        return {
            search: unitSearch || undefined,
            shop_id: unitShopId ?? '',
            condition: unitCondition || undefined,
            status: unitStatus || undefined,
            ...extra,
        };
    }

    useEffect(() => {
        if (skipNextUnitsFetch.current) {
            skipNextUnitsFetch.current = false;
            return;
        }

        const timeout = setTimeout(() => {
            router.get(`/admin/inventory/products/${sku.id}`, unitQuery(), {
                only: ['units', 'unitFilters'],
                preserveState: true,
                preserveScroll: true,
                replace: true,
                showProgress: false,
                onStart: () => setUnitsLoading(true),
                onFinish: () => setUnitsLoading(false),
            });
        }, 350);

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [unitSearch, unitShopId, unitCondition, unitStatus]);

    function goToUnitsPage(page: number) {
        router.get(`/admin/inventory/products/${sku.id}`, unitQuery({ page }), {
            only: ['units', 'unitFilters'],
            preserveState: true,
            preserveScroll: true,
            replace: true,
            showProgress: false,
            onStart: () => setUnitsLoading(true),
            onFinish: () => setUnitsLoading(false),
        });
    }

    function refreshUnits() {
        router.reload({ only: ['units'], onStart: () => setUnitsLoading(true), onFinish: () => setUnitsLoading(false) });
    }

    const unitColumns: DataTableColumn<SerializedUnit>[] = [
        { key: 'imei', label: 'IMEI' },
        { key: 'serial_number', label: 'Serial', render: (row) => row.serial_number ?? '-' },
        { key: 'shop_id', label: 'Shop', render: (row) => shops.find((shop) => shop.id === row.shop_id)?.name ?? 'Unknown shop' },
        {
            key: 'condition',
            label: 'Condition',
            render: (row) => (
                <button
                    type="button"
                    onClick={() => setEditingUnit(row)}
                    className="inline-flex items-center gap-1.5 text-admin-text hover:text-admin-blue"
                >
                    {formatInventoryCondition(row.condition)}
                    <Icon name="pencil" className="text-admin-text3 text-[11px]" />
                </button>
            ),
        },
        {
            key: 'status',
            label: 'Status',
            render: (row) => <AdminBadge status={UNIT_STATUS_BADGE[row.status] ?? 'neutral'}>{row.status}</AdminBadge>,
        },
    ];

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
                actions={<AdminButton variant="neutral" onClick={() => setEditingProduct(true)}>Edit product</AdminButton>}
            />

            {editingProduct && <EditProductModal sku={sku} onClose={() => setEditingProduct(false)} />}
            {editingUnit && (
                <EditUnitConditionModal unit={editingUnit} conditions={conditions} onClose={() => setEditingUnit(null)} />
            )}

            <div className="mb-6 grid gap-4 sm:grid-cols-[200px_minmax(0,1fr)] items-start">
                <ProductImage src={sku.image_url} alt={`${sku.brand} ${sku.model}`} aspect="square" category={sku.category} className="max-w-[200px]" />

                <div>
                    <span className="text-xs font-semibold text-admin-text2 block mb-1.5">Storefront description</span>
                    <p className="text-sm text-admin-text2 whitespace-pre-wrap">
                        {sku.description || 'No description yet — this product will show no copy on the storefront.'}
                    </p>
                </div>
            </div>

            {/* UI/UX §14C.8's headline stat row — the total across every shop, so "how many of this do we have" has one clear answer regardless of serialized vs. bulk. Per-shop detail is still below, for the breakdown. */}
            <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                <AdminStatCard label="On hand" value={String(sku.on_hand)} />
                <AdminStatCard label="Reserved" value={String(sku.reserved)} />
                <AdminStatCard label="Available" value={String(sku.available)} />
                <AdminStatCard label="Low-stock threshold" value={sku.low_stock_threshold !== null ? String(sku.low_stock_threshold) : '—'} />
            </div>

            <div>
                <AdminInfoCard title="Details" icon="info-circle" iconColor="blue" className="max-w-xl">
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
                        <div className="flex justify-between py-2.5 gap-3">
                            <dt className="text-admin-text2 shrink-0">Specs</dt>
                            <dd className="text-admin-text text-right">{variantLabel(sku.attributes)}</dd>
                        </div>
                    </dl>
                </AdminInfoCard>
            </div>

            {/* A real browsable table, not an AdminInfoCard — a serialized SKU
                can easily have 50+ units across several shops, so this needs
                its own search/filters and pagination rather than listing
                every unit from one unpaginated prop (CPNC's admin table
                pattern, same ResponsiveDataTable as Inventory > Serialized
                units). */}
            {sku.is_serialized && units ? (
                <div className="mt-8">
                    <h2 className="text-admin-md font-semibold text-admin-text mb-4">Units by shop</h2>
                    <ResponsiveDataTable
                        columns={unitColumns}
                        rows={units.data}
                        getRowKey={(row) => row.id}
                        mobilePrimaryField="imei"
                        mobileStatusField="status"
                        mobileDetailFields={['serial_number', 'shop_id', 'condition']}
                        loading={unitsLoading}
                        toolbar={
                            <>
                                <div className="relative flex-1 min-w-[160px] max-w-[260px]">
                                    <Icon name="search" className="absolute left-2.5 top-1/2 -translate-y-1/2 text-admin-text3 text-xs" />
                                    <input
                                        type="text"
                                        value={unitSearch}
                                        onChange={(e) => setUnitSearch(e.target.value)}
                                        placeholder="Filter by IMEI or serial…"
                                        className="w-full border border-admin-border rounded-admin-button pl-8 pr-3 py-1.5 text-xs bg-admin-bg outline-none focus:border-admin-blue focus:ring-2 focus:ring-admin-focus"
                                    />
                                </div>
                                <AdminSelect
                                    aria-label="Filter by shop"
                                    value={unitShopId ?? ''}
                                    onChange={(e) => setUnitShopId(e.target.value ? Number(e.target.value) : null)}
                                    containerClassName="mb-0"
                                >
                                    <option value="">All shops</option>
                                    {shops.map((shop) => (
                                        <option key={shop.id} value={shop.id}>
                                            {shop.name}
                                        </option>
                                    ))}
                                </AdminSelect>
                                <AdminSelect
                                    aria-label="Filter by condition"
                                    value={unitCondition}
                                    onChange={(e) => setUnitCondition(e.target.value)}
                                    containerClassName="mb-0"
                                >
                                    <option value="">All conditions</option>
                                    {conditions.map((condition) => (
                                        <option key={condition.id} value={condition.name}>
                                            {formatInventoryCondition(condition.name)}
                                        </option>
                                    ))}
                                </AdminSelect>
                                <AdminSelect
                                    aria-label="Filter by status"
                                    value={unitStatus}
                                    onChange={(e) => setUnitStatus(e.target.value)}
                                    containerClassName="mb-0"
                                >
                                    <option value="">All statuses</option>
                                    <option value="available">Available</option>
                                    <option value="reserved">Reserved</option>
                                    <option value="transferring">Transferring</option>
                                    <option value="sold">Sold</option>
                                    <option value="damaged">Damaged</option>
                                </AdminSelect>
                                <div className="flex-1" />
                                <AdminButton type="button" variant="neutral" onClick={refreshUnits} isLoading={unitsLoading} loadingText="Refreshing…">
                                    Refresh
                                </AdminButton>
                            </>
                        }
                        renderActions={(row) => (
                            <>
                                {row.status === 'available' && (
                                    <button
                                        type="button"
                                        disabled={updatingUnitId === row.id}
                                        onClick={() => updateUnitStatus(row.id, 'damaged')}
                                        className="text-xs font-semibold text-admin-red disabled:opacity-50"
                                    >
                                        Mark damaged
                                    </button>
                                )}
                                {row.status === 'damaged' && (
                                    <button
                                        type="button"
                                        disabled={updatingUnitId === row.id}
                                        onClick={() => updateUnitStatus(row.id, 'available')}
                                        className="text-xs font-semibold text-admin-blue disabled:opacity-50"
                                    >
                                        Restore
                                    </button>
                                )}
                            </>
                        )}
                        emptyState={
                            <AdminEmptyState
                                icon="upc-scan"
                                title="No units found"
                                description={
                                    unitFilters.search || unitFilters.shop_id || unitFilters.condition || unitFilters.status
                                        ? 'No units match these filters.'
                                        : 'No units received yet.'
                                }
                            />
                        }
                        footer={
                            units.total > 0 ? (
                                <AdminPagination
                                    currentPage={units.current_page}
                                    totalItems={units.total}
                                    pageSize={units.per_page}
                                    onPageChange={goToUnitsPage}
                                />
                            ) : undefined
                        }
                    />
                </div>
            ) : (
                <div className="mt-8">
                    <AdminInfoCard title="Stock by shop" icon="shop" iconColor="green">
                        {sku.stock_by_shop.length === 0 ? (
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
            )}

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
                <h2 className="text-admin-md font-semibold text-admin-text mb-4">Receive stock</h2>
                <form onSubmit={submitReceive}>
                    <AdminSelect label="Shop" value={data.shop_id} onChange={(e) => setData('shop_id', e.target.value)}>
                        {shops.map((shop) => (
                            <option key={shop.id} value={shop.id}>
                                {shop.name} ({shop.sku_prefix_code})
                            </option>
                        ))}
                    </AdminSelect>

                    <AdminSelect label="Condition" value={data.condition} onChange={(e) => setData('condition', e.target.value)} error={errors.condition}>
                        {conditions.map((condition) => (
                            <option key={condition.id} value={condition.name}>
                                {formatInventoryCondition(condition.name)}
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
        </AdminShell>
    );
}
