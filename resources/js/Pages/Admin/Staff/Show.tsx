import { FormEvent, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AdminShell from '../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../Components/Admin/AdminPageHead';
import AdminBadge from '../../../Components/Admin/AdminBadge';
import AdminButton from '../../../Components/Admin/AdminButton';
import Icon from '../../../Components/Icons/Icon';

function RevokeButton({ label, onClick }: { label: string; onClick: () => void }) {
    return (
        <button
            type="button"
            onClick={onClick}
            aria-label={`Revoke ${label}`}
            title="Revoke"
            className="w-5 h-5 shrink-0 inline-flex items-center justify-center rounded-full border border-admin-border-danger bg-admin-red-soft text-admin-red hover:bg-red-100"
        >
            <Icon name="x" className="text-[11px]" />
        </button>
    );
}

type StaffMember = {
    id: number;
    name: string;
    email: string;
    phone: string;
    status: 'active' | 'deactivated';
    roles: string[];
};

type Shop = { id: number; name: string; sku_prefix_code: string };

interface StaffShowProps {
    staffMember: StaffMember;
    grantedShopIds: number[];
    availableRoles: string[];
    shops: Shop[];
}

export default function StaffShow({ staffMember, grantedShopIds, availableRoles, shops }: StaffShowProps) {
    const { post: postAction, delete: deleteAction, processing } = useForm();
    const assignableRoles = availableRoles.filter((role) => !staffMember.roles.includes(role));
    const grantedShops = shops.filter((shop) => grantedShopIds.includes(shop.id));
    const grantableShops = shops.filter((shop) => !grantedShopIds.includes(shop.id));

    const [roleToAssign, setRoleToAssign] = useState(assignableRoles[0] ?? '');
    const [shopToGrant, setShopToGrant] = useState<number | ''>(grantableShops[0]?.id ?? '');

    function assignRole(e: FormEvent) {
        e.preventDefault();
        if (!roleToAssign) return;
        router.post(`/admin/staff/${staffMember.id}/roles`, { role_name: roleToAssign }, { preserveScroll: true });
    }

    function revokeRole(role: string) {
        router.delete(`/admin/staff/${staffMember.id}/roles`, { data: { role_name: role }, preserveScroll: true });
    }

    function grantShop(e: FormEvent) {
        e.preventDefault();
        if (!shopToGrant) return;
        router.post(`/admin/staff/${staffMember.id}/shops`, { shop_id: shopToGrant }, { preserveScroll: true });
    }

    function revokeShop(shopId: number) {
        router.delete(`/admin/staff/${staffMember.id}/shops`, { data: { shop_id: shopId }, preserveScroll: true });
    }

    function deactivate() {
        postAction(`/admin/staff/${staffMember.id}/deactivate`);
    }

    return (
        <AdminShell>
            <Head title={staffMember.name} />

            <AdminPageHead
                title={staffMember.name}
                description={staffMember.email}
                backHref="/admin/staff"
                backLabel="Back to staff"
                actions={
                    <>
                        <AdminButton variant="neutral" onClick={() => router.visit(`/admin/staff/${staffMember.id}/edit`)}>
                            Edit
                        </AdminButton>
                        {staffMember.status === 'active' && (
                            <AdminButton variant="danger" isLoading={processing} onClick={deactivate}>
                                Deactivate
                            </AdminButton>
                        )}
                    </>
                }
            />

            <div className="grid gap-6 sm:grid-cols-2">
                <div className="bg-admin-surface border border-admin-border rounded-admin-card p-6">
                    <h2 className="text-admin-md font-semibold text-admin-text mb-4">Details</h2>
                    <dl className="space-y-2 text-sm">
                        <div className="flex justify-between">
                            <dt className="text-admin-text2">Phone</dt>
                            <dd className="text-admin-text">{staffMember.phone}</dd>
                        </div>
                        <div className="flex justify-between">
                            <dt className="text-admin-text2">Status</dt>
                            <dd>
                                <AdminBadge status={staffMember.status === 'active' ? 'success' : 'neutral'}>
                                    {staffMember.status === 'active' ? 'Active' : 'Deactivated'}
                                </AdminBadge>
                            </dd>
                        </div>
                    </dl>
                </div>

                <div className="bg-admin-surface border border-admin-border rounded-admin-card p-6">
                    <h2 className="text-admin-md font-semibold text-admin-text mb-4">Shop access</h2>
                    <div className="flex flex-col gap-2 mb-3">
                        {grantedShops.length === 0 && <span className="text-admin-text2 text-sm">No shop access granted.</span>}
                        {grantedShops.map((shop) => (
                            <div key={shop.id} className="flex items-center justify-between gap-2 text-sm">
                                <span className="text-admin-text truncate">
                                    {shop.name} ({shop.sku_prefix_code})
                                </span>
                                <RevokeButton label={shop.name} onClick={() => revokeShop(shop.id)} />
                            </div>
                        ))}
                    </div>
                    {grantableShops.length > 0 && (
                        <form onSubmit={grantShop} className="flex items-center gap-2">
                            <select
                                value={shopToGrant}
                                onChange={(e) => setShopToGrant(Number(e.target.value))}
                                className="flex-1 min-w-0 border border-admin-border-strong rounded-admin-button text-sm py-1.5 px-2"
                            >
                                {grantableShops.map((shop) => (
                                    <option key={shop.id} value={shop.id}>
                                        {shop.name} ({shop.sku_prefix_code})
                                    </option>
                                ))}
                            </select>
                            <AdminButton type="submit" variant="neutral">
                                Grant access
                            </AdminButton>
                        </form>
                    )}
                </div>
            </div>

            <div className="mt-6 bg-admin-surface border border-admin-border rounded-admin-card p-6">
                <h2 className="text-admin-md font-semibold text-admin-text mb-4">Roles</h2>
                <div className="flex flex-wrap gap-3 mb-4">
                    {staffMember.roles.length === 0 && <span className="text-admin-text2 text-sm">No roles assigned.</span>}
                    {staffMember.roles.map((role) => (
                        <span
                            key={role}
                            className="inline-flex items-center gap-2 bg-admin-blue-soft rounded-admin-badge pl-2.5 pr-1.5 py-1"
                        >
                            <span className="text-xs font-semibold text-admin-blue">{role}</span>
                            <RevokeButton label={role} onClick={() => revokeRole(role)} />
                        </span>
                    ))}
                </div>
                {assignableRoles.length > 0 && (
                    <form onSubmit={assignRole} className="flex items-center gap-2 max-w-sm">
                        <select
                            value={roleToAssign}
                            onChange={(e) => setRoleToAssign(e.target.value)}
                            className="flex-1 min-w-0 border border-admin-border-strong rounded-admin-button text-sm py-1.5 px-2"
                        >
                            {assignableRoles.map((role) => (
                                <option key={role} value={role}>
                                    {role}
                                </option>
                            ))}
                        </select>
                        <AdminButton type="submit" variant="neutral">
                            Assign role
                        </AdminButton>
                    </form>
                )}
            </div>
        </AdminShell>
    );
}
