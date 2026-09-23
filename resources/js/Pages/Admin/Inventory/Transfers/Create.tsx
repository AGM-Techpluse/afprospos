import { FormEvent, useEffect, useRef, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AdminShell from '../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../Components/Admin/AdminPageHead';
import AdminInput from '../../../../Components/Admin/Forms/AdminInput';
import AdminSelect from '../../../../Components/Admin/Forms/AdminSelect';
import AdminButton from '../../../../Components/Admin/AdminButton';
import AdminEmptyState from '../../../../Components/Admin/AdminEmptyState';
import Icon from '../../../../Components/Icons/Icon';

type Shop = { id: number; name: string; sku_prefix_code: string };

type StockResult = {
    sku_id: number;
    sku_code: string;
    brand: string;
    model: string;
    category: string;
    available: number;
};

/** Bulk transfer of non-serialized stock — search/category picker + checkboxes (reuses the existing InventoryStockController::bulkTransfer write path, the same one Stock/Index's bulk action already posts to). */
function BulkTransferPicker({ shops, categories }: { shops: Shop[]; categories: string[] }) {
    const [fromShopId, setFromShopId] = useState(shops[0]?.id.toString() ?? '');
    const [toShopId, setToShopId] = useState(shops[1]?.id.toString() ?? shops[0]?.id.toString() ?? '');
    const [category, setCategory] = useState('');
    const [term, setTerm] = useState('');
    const [results, setResults] = useState<StockResult[]>([]);
    const [loading, setLoading] = useState(false);
    const requestId = useRef(0);

    const [selected, setSelected] = useState<Record<number, boolean>>({});
    const [quantities, setQuantities] = useState<Record<number, string>>({});
    const [processing, setProcessing] = useState(false);

    useEffect(() => {
        if (!fromShopId) return;

        const currentRequest = ++requestId.current;
        const timeout = setTimeout(() => {
            setLoading(true);
            const params = new URLSearchParams({ from_shop_id: fromShopId });
            if (term.trim() !== '') params.set('search', term.trim());
            if (category !== '') params.set('category', category);

            fetch(`/admin/inventory/transfers/search?${params.toString()}`, { credentials: 'same-origin' })
                .then((response) => response.json())
                .then((json: { results: StockResult[] }) => {
                    if (requestId.current === currentRequest) {
                        setResults(json.results);
                        setLoading(false);
                    }
                })
                .catch(() => setLoading(false));
        }, 300);

        return () => clearTimeout(timeout);
    }, [fromShopId, category, term]);

    function toggle(row: StockResult) {
        setSelected((current) => {
            const next = { ...current, [row.sku_id]: !current[row.sku_id] };
            if (next[row.sku_id] && quantities[row.sku_id] === undefined) {
                setQuantities((currentQty) => ({ ...currentQty, [row.sku_id]: String(row.available) }));
            }
            return next;
        });
    }

    const selectedRows = results.filter((row) => selected[row.sku_id]);

    function submit(e: FormEvent) {
        e.preventDefault();
        setProcessing(true);

        router.post(
            '/admin/inventory/stock/bulk-transfer',
            {
                to_shop_id: toShopId,
                items: selectedRows.map((row) => ({
                    sku_id: row.sku_id,
                    from_shop_id: fromShopId,
                    quantity: parseInt(quantities[row.sku_id] || '0', 10),
                })),
            },
            { onFinish: () => setProcessing(false) },
        );
    }

    return (
        <form onSubmit={submit}>
            <div className="grid sm:grid-cols-2 gap-4 mb-4">
                <AdminSelect label="From shop" value={fromShopId} onChange={(e) => setFromShopId(e.target.value)}>
                    {shops.map((shop) => (
                        <option key={shop.id} value={shop.id}>
                            {shop.name} ({shop.sku_prefix_code})
                        </option>
                    ))}
                </AdminSelect>
                <AdminSelect label="To shop" value={toShopId} onChange={(e) => setToShopId(e.target.value)}>
                    {shops.map((shop) => (
                        <option key={shop.id} value={shop.id}>
                            {shop.name} ({shop.sku_prefix_code})
                        </option>
                    ))}
                </AdminSelect>
            </div>

            <div className="grid sm:grid-cols-[200px_1fr] gap-4 mb-4">
                <AdminSelect label="Category" value={category} onChange={(e) => setCategory(e.target.value)}>
                    <option value="">All categories</option>
                    {categories.map((c) => (
                        <option key={c} value={c}>
                            {c}
                        </option>
                    ))}
                </AdminSelect>
                <div>
                    <label className="block text-xs font-semibold text-admin-text mb-1.5">Search</label>
                    <div className="relative">
                        <Icon name="search" className="absolute left-3 top-1/2 -translate-y-1/2 text-admin-text3" />
                        <input
                            type="text"
                            value={term}
                            onChange={(e) => setTerm(e.target.value)}
                            placeholder="Search by name, brand, or SKU…"
                            className="w-full border border-admin-border-strong rounded-admin-button pl-9 pr-3 py-2 text-admin-field outline-none focus:border-admin-blue focus:ring-2 focus:ring-admin-focus"
                        />
                    </div>
                </div>
            </div>

            <div className="border border-admin-border rounded-admin-card mb-4 max-h-[420px] overflow-y-auto">
                {loading ? (
                    <p className="text-sm text-admin-text3 p-4">Searching…</p>
                ) : results.length === 0 ? (
                    <AdminEmptyState
                        icon="box-seam"
                        title={fromShopId ? 'No matching products at this shop' : 'Pick a from-shop to start'}
                        description="Try a different search term or category."
                    />
                ) : (
                    <table className="w-full text-sm">
                        <thead className="bg-admin-hover text-xs text-admin-text2 sticky top-0">
                            <tr>
                                <th className="w-10 p-3"></th>
                                <th className="text-left p-3">Product</th>
                                <th className="text-left p-3">SKU</th>
                                <th className="text-left p-3">Category</th>
                                <th className="text-right p-3">Available</th>
                                <th className="text-right p-3 w-28">Quantity</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-admin-border">
                            {results.map((row) => (
                                <tr key={row.sku_id} className={selected[row.sku_id] ? 'bg-admin-blue/5' : undefined}>
                                    <td className="p-3">
                                        <input
                                            type="checkbox"
                                            checked={!!selected[row.sku_id]}
                                            onChange={() => toggle(row)}
                                            disabled={row.available < 1}
                                        />
                                    </td>
                                    <td className="p-3 text-admin-text">
                                        {row.brand} {row.model}
                                    </td>
                                    <td className="p-3 text-admin-text3">{row.sku_code}</td>
                                    <td className="p-3 text-admin-text3">{row.category}</td>
                                    <td className="p-3 text-right text-admin-text">{row.available}</td>
                                    <td className="p-3 text-right">
                                        {selected[row.sku_id] && (
                                            <input
                                                type="number"
                                                min={1}
                                                max={row.available}
                                                value={quantities[row.sku_id] ?? ''}
                                                onChange={(e) => setQuantities((current) => ({ ...current, [row.sku_id]: e.target.value }))}
                                                className="w-20 border border-admin-border-strong rounded-admin-button px-2 py-1 text-xs text-right"
                                            />
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </div>

            <AdminButton type="submit" isLoading={processing} disabled={selectedRows.length === 0 || fromShopId === toShopId}>
                Transfer {selectedRows.length > 0 ? `${selectedRows.length} item${selectedRows.length === 1 ? '' : 's'}` : ''}
            </AdminButton>
            {fromShopId === toShopId && <p className="text-xs text-admin-red mt-2">From shop and to shop must be different.</p>}
        </form>
    );
}

/** Single serialized (IMEI-tracked) unit — quantity doesn't apply to an individually-tracked device, so it keeps its own simple form. */
function SerializedUnitTransferForm({ shops }: { shops: Shop[] }) {
    const { data, setData, post, processing, errors } = useForm({
        sku_id: '',
        inventory_item_id: '',
        from_shop_id: shops[0]?.id.toString() ?? '',
        to_shop_id: shops[1]?.id.toString() ?? shops[0]?.id.toString() ?? '',
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        post('/admin/inventory/transfers');
    }

    return (
        <form onSubmit={submit} className="max-w-[480px]">
            <AdminInput
                label="SKU ID"
                type="number"
                value={data.sku_id}
                onChange={(e) => setData('sku_id', e.target.value)}
                error={errors.sku_id}
                required
            />
            <AdminInput
                label="Inventory item ID (IMEI unit)"
                type="number"
                value={data.inventory_item_id}
                onChange={(e) => setData('inventory_item_id', e.target.value)}
                error={errors.inventory_item_id}
                required
            />
            <AdminSelect label="From shop" value={data.from_shop_id} onChange={(e) => setData('from_shop_id', e.target.value)}>
                {shops.map((shop) => (
                    <option key={shop.id} value={shop.id}>
                        {shop.name} ({shop.sku_prefix_code})
                    </option>
                ))}
            </AdminSelect>
            <AdminSelect label="To shop" value={data.to_shop_id} onChange={(e) => setData('to_shop_id', e.target.value)} error={errors.to_shop_id}>
                {shops.map((shop) => (
                    <option key={shop.id} value={shop.id}>
                        {shop.name} ({shop.sku_prefix_code})
                    </option>
                ))}
            </AdminSelect>
            <AdminButton type="submit" isLoading={processing}>
                Transfer unit
            </AdminButton>
        </form>
    );
}

export default function TransferCreate({ shops, categories }: { shops: Shop[]; categories: string[] }) {
    const [mode, setMode] = useState<'bulk' | 'serialized'>('bulk');

    return (
        <AdminShell>
            <Head title="New transfer" />

            <AdminPageHead
                title="New transfer"
                description="Move stock between shops."
                backHref="/admin/inventory/transfers"
                backLabel="Back to transfers"
            />

            <div className="flex items-center gap-2 mb-5">
                <button
                    type="button"
                    onClick={() => setMode('bulk')}
                    className={`text-xs font-semibold px-3 py-1.5 rounded-admin-button ${mode === 'bulk' ? 'bg-admin-blue text-white' : 'bg-admin-hover text-admin-text2'}`}
                >
                    Bulk transfer
                </button>
                <button
                    type="button"
                    onClick={() => setMode('serialized')}
                    className={`text-xs font-semibold px-3 py-1.5 rounded-admin-button ${mode === 'serialized' ? 'bg-admin-blue text-white' : 'bg-admin-hover text-admin-text2'}`}
                >
                    Single serialized unit (IMEI)
                </button>
            </div>

            {mode === 'bulk' ? <BulkTransferPicker shops={shops} categories={categories} /> : <SerializedUnitTransferForm shops={shops} />}
        </AdminShell>
    );
}
