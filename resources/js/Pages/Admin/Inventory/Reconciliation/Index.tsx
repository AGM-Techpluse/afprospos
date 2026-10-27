import { FormEvent, useMemo, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminShell from '../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../Components/Admin/AdminPageHead';
import AdminButton from '../../../../Components/Admin/AdminButton';
import AdminSelect from '../../../../Components/Admin/Forms/AdminSelect';
import AdminEmptyState from '../../../../Components/Admin/AdminEmptyState';
import InventorySubNav from '../../../../Components/Admin/InventorySubNav';

type StockRow = {
    id: number;
    sku_id: number;
    sku_code: string;
    brand: string;
    model: string;
    category: string;
    shop_id: number;
    on_hand: number;
    reserved: number;
    available: number;
};

type Shop = { id: number; name: string };

interface ReconciliationIndexProps {
    stock: StockRow[];
    filters: { shop_id: number | null; category: string };
    shops: Shop[];
    categories: string[];
    reasons: Record<string, string>;
}

type RowState = { physical: string; reasonCode: string; note: string };

const EMPTY_ROW: RowState = { physical: '', reasonCode: '', note: '' };

export default function ReconciliationIndex({ stock, filters, shops, categories, reasons }: ReconciliationIndexProps) {
    const [shopId, setShopId] = useState(filters.shop_id);
    const [category, setCategory] = useState(filters.category);
    const [rows, setRows] = useState<Record<number, RowState>>({});
    const [submitting, setSubmitting] = useState(false);

    function applyFilters(next: Partial<{ shop_id: number | null; category: string }>) {
        router.get(
            '/admin/inventory/reconciliation',
            { shop_id: next.shop_id !== undefined ? (next.shop_id ?? '') : (shopId ?? ''), category: next.category ?? category },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function updateRow(skuId: number, patch: Partial<RowState>) {
        setRows((current) => ({
            ...current,
            [skuId]: { ...EMPTY_ROW, ...current[skuId], ...patch },
        }));
    }

    const discrepancyFor = (row: StockRow): number | null => {
        const physical = rows[row.sku_id]?.physical;
        if (physical === undefined || physical.trim() === '') return null;
        const parsed = parseInt(physical, 10);
        if (Number.isNaN(parsed)) return null;
        return parsed - row.on_hand;
    };

    const pendingCount = useMemo(
        () => stock.filter((row) => {
            const discrepancy = discrepancyFor(row);
            return discrepancy !== null && discrepancy !== 0;
        }).length,
        // eslint-disable-next-line react-hooks/exhaustive-deps
        [rows, stock],
    );

    function rowIsReady(row: StockRow): boolean {
        const discrepancy = discrepancyFor(row);
        if (discrepancy === null || discrepancy === 0) return false;
        const state = rows[row.sku_id];
        if (!state || state.reasonCode === '') return false;
        if (state.reasonCode === 'other' && state.note.trim() === '') return false;
        return true;
    }

    const canSubmit = useMemo(
        () => stock.some((row) => rowIsReady(row)),
        // eslint-disable-next-line react-hooks/exhaustive-deps
        [rows, stock],
    );

    function submit(e: FormEvent) {
        e.preventDefault();
        if (shopId === null) return;

        const counts = stock
            .map((row) => {
                if (!rowIsReady(row)) return null;
                const state = rows[row.sku_id];

                return {
                    sku_id: row.sku_id,
                    shop_id: row.shop_id,
                    physical_count: parseInt(state.physical, 10),
                    reason_code: state.reasonCode,
                    note: state.note.trim() === '' ? null : state.note.trim(),
                };
            })
            .filter((count): count is NonNullable<typeof count> => count !== null);

        if (counts.length === 0) return;

        setSubmitting(true);
        router.post(
            '/admin/inventory/reconciliation',
            { counts },
            {
                preserveScroll: true,
                onSuccess: () => setRows({}),
                onFinish: () => setSubmitting(false),
            },
        );
    }

    return (
        <AdminShell>
            <Head title="Stock reconciliation" />

            <AdminPageHead
                title="Inventory"
                description="Compare a physical stock count against the system's recorded on-hand quantity, and record why they differ."
            />

            <InventorySubNav />

            <div className="flex flex-wrap items-center gap-2 mb-4">
                <AdminSelect
                    aria-label="Shop"
                    value={shopId ?? ''}
                    onChange={(e) => {
                        const next = e.target.value ? Number(e.target.value) : null;
                        setShopId(next);
                        applyFilters({ shop_id: next });
                    }}
                    containerClassName="mb-0 min-w-[180px]"
                >
                    <option value="">Select a shop…</option>
                    {shops.map((shop) => (
                        <option key={shop.id} value={shop.id}>
                            {shop.name}
                        </option>
                    ))}
                </AdminSelect>
                <AdminSelect
                    aria-label="Category"
                    value={category}
                    onChange={(e) => {
                        setCategory(e.target.value);
                        applyFilters({ category: e.target.value });
                    }}
                    containerClassName="mb-0 min-w-[160px]"
                >
                    <option value="">All categories</option>
                    {categories.map((name) => (
                        <option key={name} value={name}>
                            {name}
                        </option>
                    ))}
                </AdminSelect>
            </div>

            {shopId === null ? (
                <AdminEmptyState icon="clipboard2-check" title="Select a shop" description="Pick a shop above to count its non-serialized stock." />
            ) : stock.length === 0 ? (
                <AdminEmptyState icon="clipboard2-check" title="No products found" description="This shop has no non-serialized stock matching that filter." />
            ) : (
                <form onSubmit={submit}>
                    <div className="bg-admin-surface border border-admin-border rounded-admin-card overflow-x-auto mb-4">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-admin-border text-left text-xs text-admin-text2">
                                    <th className="p-3 font-medium">SKU</th>
                                    <th className="p-3 font-medium">System count</th>
                                    <th className="p-3 font-medium">Physical count</th>
                                    <th className="p-3 font-medium">Discrepancy</th>
                                    <th className="p-3 font-medium">Reason</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-admin-border">
                                {stock.map((row) => {
                                    const discrepancy = discrepancyFor(row);
                                    const state = rows[row.sku_id];

                                    return (
                                        <tr key={row.sku_id}>
                                            <td className="p-3">
                                                <div className="font-medium text-admin-text">{row.sku_code}</div>
                                                <div className="text-xs text-admin-text3">{row.brand} {row.model}</div>
                                            </td>
                                            <td className="p-3 text-admin-text2">{row.on_hand}</td>
                                            <td className="p-3">
                                                <input
                                                    type="number"
                                                    min={0}
                                                    value={state?.physical ?? ''}
                                                    onChange={(e) => updateRow(row.sku_id, { physical: e.target.value })}
                                                    placeholder={String(row.on_hand)}
                                                    className="w-24 border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-sm"
                                                />
                                            </td>
                                            <td className="p-3">
                                                {discrepancy === null ? (
                                                    <span className="text-admin-text3">—</span>
                                                ) : discrepancy === 0 ? (
                                                    <span className="text-admin-green-text font-semibold">Match</span>
                                                ) : (
                                                    <span className={`font-semibold ${discrepancy > 0 ? 'text-admin-blue' : 'text-admin-red'}`}>
                                                        {discrepancy > 0 ? '+' : ''}{discrepancy}
                                                    </span>
                                                )}
                                            </td>
                                            <td className="p-3 min-w-[220px]">
                                                {discrepancy !== null && discrepancy !== 0 && (
                                                    <div className="flex flex-col gap-1.5">
                                                        <AdminSelect
                                                            aria-label={`Reason for ${row.sku_code}`}
                                                            value={state?.reasonCode ?? ''}
                                                            onChange={(e) => updateRow(row.sku_id, { reasonCode: e.target.value })}
                                                            containerClassName="mb-0"
                                                        >
                                                            <option value="">Select a reason…</option>
                                                            {Object.entries(reasons).map(([value, label]) => (
                                                                <option key={value} value={value}>
                                                                    {label}
                                                                </option>
                                                            ))}
                                                        </AdminSelect>
                                                        {state?.reasonCode === 'other' && (
                                                            <input
                                                                value={state?.note ?? ''}
                                                                onChange={(e) => updateRow(row.sku_id, { note: e.target.value })}
                                                                placeholder='Note (required for "Other")'
                                                                className="w-full border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs"
                                                            />
                                                        )}
                                                    </div>
                                                )}
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>

                    <div className="flex items-center gap-3">
                        <AdminButton type="submit" isLoading={submitting} disabled={!canSubmit}>
                            Submit reconciliation{pendingCount > 0 ? ` (${pendingCount})` : ''}
                        </AdminButton>
                        <span className="text-xs text-admin-text3">Only rows with a discrepancy and a selected reason are submitted.</span>
                    </div>
                </form>
            )}
        </AdminShell>
    );
}
