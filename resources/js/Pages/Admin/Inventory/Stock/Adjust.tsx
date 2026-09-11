import { FormEvent } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AdminShell from '../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../Components/Admin/AdminPageHead';
import AdminInput from '../../../../Components/Admin/Forms/AdminInput';
import AdminTextarea from '../../../../Components/Admin/Forms/AdminTextarea';
import AdminButton from '../../../../Components/Admin/AdminButton';

type StockLevel = {
    sku_id: number;
    sku_code: string;
    brand: string;
    model: string;
    shop_id: number;
    on_hand: number;
    reserved: number;
    available: number;
};

export default function StockAdjust({ level }: { level: StockLevel }) {
    const { data, setData, post, processing, errors } = useForm({
        delta: '',
        reason: '',
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        post(`/admin/inventory/stock/${level.sku_id}/${level.shop_id}/adjust`);
    }

    const deltaNumber = parseInt(data.delta || '0', 10);
    const resultingOnHand = level.on_hand + (Number.isNaN(deltaNumber) ? 0 : deltaNumber);

    return (
        <AdminShell>
            <Head title={`Adjust ${level.sku_code}`} />

            <AdminPageHead
                title={`Adjust ${level.brand} ${level.model}`}
                description={`${level.sku_code} — Shop #${level.shop_id}: ${level.on_hand} on hand, ${level.reserved} reserved, ${level.available} available.`}
            />

            <form onSubmit={submit} className="max-w-[640px]">
                <AdminInput
                    label="Adjustment (+ or -)"
                    type="number"
                    value={data.delta}
                    onChange={(e) => setData('delta', e.target.value)}
                    error={errors.delta}
                    placeholder="e.g. -3 or 10"
                    required
                />
                <p className="text-xs text-admin-text2 -mt-3 mb-5">
                    Resulting on hand: <span className="font-semibold text-admin-text">{resultingOnHand}</span>
                </p>

                <AdminTextarea
                    label="Reason"
                    value={data.reason}
                    onChange={(e) => setData('reason', e.target.value)}
                    error={errors.reason}
                    placeholder="e.g. Damaged in storage, stock count correction…"
                    required
                />

                <div className="flex items-center gap-2">
                    <AdminButton type="submit" isLoading={processing}>
                        Save adjustment
                    </AdminButton>
                    <AdminButton type="button" variant="neutral" onClick={() => router.visit('/admin/inventory/stock')}>
                        Cancel
                    </AdminButton>
                </div>
            </form>
        </AdminShell>
    );
}
