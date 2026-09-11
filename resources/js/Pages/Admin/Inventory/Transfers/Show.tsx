import { Head, router, useForm } from '@inertiajs/react';
import AdminShell from '../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../Components/Admin/AdminPageHead';
import AdminBadge from '../../../../Components/Admin/AdminBadge';
import AdminButton from '../../../../Components/Admin/AdminButton';

type Transfer = {
    id: number;
    sku_code: string;
    brand: string;
    model: string;
    inventory_item_id: number | null;
    quantity: number | null;
    from_shop_id: number;
    to_shop_id: number;
    initiated_by_staff_id: number;
    received_by_staff_id: number | null;
    status: 'in_transit' | 'completed' | 'cancelled';
    created_at: string;
};

export default function TransferShow({ transfer }: { transfer: Transfer }) {
    const { post, processing } = useForm();

    function receive() {
        post(`/admin/inventory/transfers/${transfer.id}/receive`);
    }

    function cancel() {
        post(`/admin/inventory/transfers/${transfer.id}/cancel`);
    }

    return (
        <AdminShell>
            <Head title={`Transfer #${transfer.id}`} />

            <AdminPageHead
                title={`Transfer #${transfer.id}`}
                description={`${transfer.sku_code} — ${transfer.brand} ${transfer.model}`}
                actions={
                    transfer.status === 'in_transit' ? (
                        <>
                            <AdminButton variant="neutral" isLoading={processing} onClick={receive}>
                                Mark received
                            </AdminButton>
                            <AdminButton variant="danger" isLoading={processing} onClick={cancel}>
                                Cancel
                            </AdminButton>
                        </>
                    ) : (
                        <AdminButton variant="neutral" onClick={() => router.visit('/admin/inventory/transfers')}>
                            Back
                        </AdminButton>
                    )
                }
            />

            <div className="max-w-lg bg-admin-surface border border-admin-border rounded-admin-card p-6">
                <dl className="space-y-3 text-sm">
                    <div className="flex justify-between">
                        <dt className="text-admin-text2">Status</dt>
                        <dd>
                            <AdminBadge status={transfer.status === 'completed' ? 'success' : transfer.status === 'cancelled' ? 'neutral' : 'info'}>
                                {transfer.status === 'in_transit' ? 'In transit' : transfer.status === 'completed' ? 'Completed' : 'Cancelled'}
                            </AdminBadge>
                        </dd>
                    </div>
                    <div className="flex justify-between">
                        <dt className="text-admin-text2">{transfer.inventory_item_id ? 'Unit' : 'Quantity'}</dt>
                        <dd className="text-admin-text">{transfer.inventory_item_id ? `Item #${transfer.inventory_item_id}` : transfer.quantity}</dd>
                    </div>
                    <div className="flex justify-between">
                        <dt className="text-admin-text2">From shop</dt>
                        <dd className="text-admin-text">#{transfer.from_shop_id}</dd>
                    </div>
                    <div className="flex justify-between">
                        <dt className="text-admin-text2">To shop</dt>
                        <dd className="text-admin-text">#{transfer.to_shop_id}</dd>
                    </div>
                    <div className="flex justify-between">
                        <dt className="text-admin-text2">Initiated by</dt>
                        <dd className="text-admin-text">Staff #{transfer.initiated_by_staff_id}</dd>
                    </div>
                    {transfer.received_by_staff_id && (
                        <div className="flex justify-between">
                            <dt className="text-admin-text2">Received by</dt>
                            <dd className="text-admin-text">Staff #{transfer.received_by_staff_id}</dd>
                        </div>
                    )}
                </dl>
            </div>
        </AdminShell>
    );
}
