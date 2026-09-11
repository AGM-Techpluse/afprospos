import { Head, router } from '@inertiajs/react';
import AdminShell from '../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../Components/Admin/AdminPageHead';
import AdminBadge from '../../../../Components/Admin/AdminBadge';
import AdminButton from '../../../../Components/Admin/AdminButton';
import AdminEmptyState from '../../../../Components/Admin/AdminEmptyState';

type LowStockItem = {
    stock_level_id: number;
    sku_id: number;
    sku_code: string;
    brand: string;
    model: string;
    shop_id: number;
    on_hand: number;
    reserved: number;
    available: number;
    low_stock_threshold: number;
};

export default function LowStock({ items }: { items: LowStockItem[] }) {
    return (
        <AdminShell>
            <Head title="Low stock" />

            <AdminPageHead
                title="Low stock"
                description="SKUs whose Available quantity has fallen to or below their configured threshold (INV-BR-07)."
                backHref="/admin/inventory/stock"
                backLabel="Back to stock"
            />

            {items.length === 0 ? (
                <div className="bg-admin-surface border border-admin-border rounded-admin-card">
                    <AdminEmptyState icon="check-circle" title="Nothing low on stock" description="Every SKU is above its configured threshold." />
                </div>
            ) : (
                <div className="bg-admin-surface border border-admin-border rounded-admin-card overflow-hidden">
                    <table className="w-full">
                        <thead>
                            <tr className="bg-admin-table-head">
                                <th className="text-left text-admin-xs font-semibold text-admin-text2 px-admin-table-cell-x py-admin-table-cell-y">SKU</th>
                                <th className="text-left text-admin-xs font-semibold text-admin-text2 px-admin-table-cell-x py-admin-table-cell-y">Shop</th>
                                <th className="text-left text-admin-xs font-semibold text-admin-text2 px-admin-table-cell-x py-admin-table-cell-y">Available</th>
                                <th className="text-left text-admin-xs font-semibold text-admin-text2 px-admin-table-cell-x py-admin-table-cell-y">Threshold</th>
                                <th className="w-10" aria-hidden="true" />
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-admin-border">
                            {items.map((item) => (
                                <tr key={item.stock_level_id} className="hover:bg-admin-hover">
                                    <td className="text-admin-table-cell text-admin-text px-admin-table-cell-x py-admin-table-cell-y">
                                        {item.sku_code} — {item.brand} {item.model}
                                    </td>
                                    <td className="text-admin-table-cell text-admin-text px-admin-table-cell-x py-admin-table-cell-y">#{item.shop_id}</td>
                                    <td className="px-admin-table-cell-x py-admin-table-cell-y">
                                        <AdminBadge status="warning">{item.available}</AdminBadge>
                                    </td>
                                    <td className="text-admin-table-cell text-admin-text px-admin-table-cell-x py-admin-table-cell-y">{item.low_stock_threshold}</td>
                                    <td className="px-admin-table-cell-x py-admin-table-cell-y text-right">
                                        <AdminButton
                                            variant="neutral"
                                            onClick={() => router.visit(`/admin/inventory/stock/${item.sku_id}/${item.shop_id}/adjust`)}
                                        >
                                            Receive / Adjust
                                        </AdminButton>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </AdminShell>
    );
}
