import { FormEvent, useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AdminShell from '../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../Components/Admin/AdminPageHead';
import AdminButton from '../../../../Components/Admin/AdminButton';
import InventorySubNav from '../../../../Components/Admin/InventorySubNav';

type Shop = { id: number; name: string; sku_prefix_code: string };

export default function ImportCreate({ shops, expectedHeader }: { shops: Shop[]; expectedHeader: string[] }) {
    const { data, setData, post, processing, errors } = useForm<{ shop_id: string; file: File | null }>({
        shop_id: shops[0]?.id.toString() ?? '',
        file: null,
    });
    const [fileName, setFileName] = useState('');

    function submit(e: FormEvent) {
        e.preventDefault();
        post('/admin/inventory/import', { forceFormData: true });
    }

    return (
        <AdminShell>
            <Head title="Import products" />

            <AdminPageHead title="Import products" description="Bulk-add products via a CSV file (BRD INV-01)." />

            <InventorySubNav />

            <div className="max-w-[640px]">
                <div className="bg-admin-surface border border-admin-border rounded-admin-card p-4 mb-6">
                    <p className="text-sm font-semibold text-admin-text mb-2">Expected CSV columns</p>
                    <code className="text-xs text-admin-text2 break-all">{expectedHeader.join(',')}</code>
                    <p className="text-xs text-admin-text2 mt-2">
                        Fill either <code>quantity</code> (bulk stock) or <code>imei</code> (one serialized unit) per row, not both.
                        <code>low_stock_threshold</code> is optional.
                    </p>
                </div>

                <form onSubmit={submit}>
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

                    <div className="mb-6">
                        <label className="block text-xs font-semibold text-admin-text mb-1.5">CSV file</label>
                        <input
                            type="file"
                            accept=".csv,text/csv"
                            onChange={(e) => {
                                const file = e.target.files?.[0] ?? null;
                                setData('file', file);
                                setFileName(file?.name ?? '');
                            }}
                            className="w-full border border-admin-border-strong rounded-admin-button px-admin-field-x py-admin-field-y text-admin-field bg-white"
                        />
                        {fileName && <p className="text-xs text-admin-text2 mt-1">{fileName}</p>}
                        {errors.file && <p className="text-xs text-admin-red mt-1">{errors.file}</p>}
                    </div>

                    <AdminButton type="submit" isLoading={processing} disabled={!data.file}>
                        Import
                    </AdminButton>
                </form>
            </div>
        </AdminShell>
    );
}
