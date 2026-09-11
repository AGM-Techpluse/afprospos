import { Head, router } from '@inertiajs/react';
import AdminShell from '../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../Components/Admin/AdminPageHead';
import AdminBadge from '../../../Components/Admin/AdminBadge';
import AdminButton from '../../../Components/Admin/AdminButton';

type Shop = {
    id: number;
    name: string;
    sku_prefix_code: string;
    address: string;
    contact_phone: string;
    contact_email: string;
    status: 'active' | 'inactive';
};

export default function ShopShow({ shop }: { shop: Shop }) {
    return (
        <AdminShell>
            <Head title={shop.name} />

            <AdminPageHead
                title={shop.name}
                description={shop.sku_prefix_code}
                backHref="/admin/shops"
                backLabel="Back to shops"
                actions={
                    <AdminButton variant="neutral" onClick={() => router.visit(`/admin/shops/${shop.id}/edit`)}>
                        Edit
                    </AdminButton>
                }
            />

            <div className="max-w-lg bg-admin-surface border border-admin-border rounded-admin-card p-6">
                <dl className="space-y-3 text-sm">
                    <div className="flex justify-between">
                        <dt className="text-admin-text2">SKU prefix code</dt>
                        <dd className="text-admin-text">{shop.sku_prefix_code}</dd>
                    </div>
                    <div className="flex justify-between">
                        <dt className="text-admin-text2">Address</dt>
                        <dd className="text-admin-text text-right">{shop.address}</dd>
                    </div>
                    <div className="flex justify-between">
                        <dt className="text-admin-text2">Contact phone</dt>
                        <dd className="text-admin-text">{shop.contact_phone}</dd>
                    </div>
                    <div className="flex justify-between">
                        <dt className="text-admin-text2">Contact email</dt>
                        <dd className="text-admin-text">{shop.contact_email}</dd>
                    </div>
                    <div className="flex justify-between">
                        <dt className="text-admin-text2">Status</dt>
                        <dd>
                            <AdminBadge status={shop.status === 'active' ? 'success' : 'neutral'}>
                                {shop.status === 'active' ? 'Active' : 'Inactive'}
                            </AdminBadge>
                        </dd>
                    </div>
                </dl>
            </div>
        </AdminShell>
    );
}
