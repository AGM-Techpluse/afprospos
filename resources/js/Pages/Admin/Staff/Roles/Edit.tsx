import { FormEvent } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AdminShell from '../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../Components/Admin/AdminPageHead';
import AdminButton from '../../../../Components/Admin/AdminButton';
import RolePreviewCard from '../../../../Components/Admin/RolePreviewCard';

type PermissionAction = { key: string; label: string };
type PermissionModule = { module: string; actions: PermissionAction[] };
type Role = { name: string; permissionNames: string[] };

interface RoleEditProps {
    role: Role;
    permissionCatalog: PermissionModule[];
}

export default function RoleEdit({ role, permissionCatalog }: RoleEditProps) {
    const { data, setData, put, processing } = useForm({
        permissions: role.permissionNames,
    });

    function toggle(key: string) {
        setData(
            'permissions',
            data.permissions.includes(key)
                ? data.permissions.filter((p) => p !== key)
                : [...data.permissions, key],
        );
    }

    function submit(e: FormEvent) {
        e.preventDefault();
        put(`/admin/staff/roles/${encodeURIComponent(role.name)}`);
    }

    return (
        <AdminShell>
            <Head title={`Edit ${role.name}`} />

            <AdminPageHead title={role.name} description="Role names can't be changed once created — only the permissions it grants." />

            <div className="grid gap-8 lg:grid-cols-[minmax(0,640px)_320px] items-start max-w-[992px] mx-auto">
                <form onSubmit={submit}>
                    <div className="mb-6">
                        <span className="block text-sm font-semibold text-admin-text mb-2">Permissions</span>
                        <div className="grid gap-4 sm:grid-cols-2">
                            {permissionCatalog.map((mod) => (
                                <div key={mod.module} className="border border-admin-border rounded-admin-button p-3">
                                    <p className="text-xs font-semibold text-admin-text2 uppercase tracking-wide mb-2">{mod.module}</p>
                                    <div className="flex flex-col gap-1.5">
                                        {mod.actions.map((action) => (
                                            <label key={action.key} className="flex items-center gap-2 text-sm text-admin-text">
                                                <input
                                                    type="checkbox"
                                                    checked={data.permissions.includes(action.key)}
                                                    onChange={() => toggle(action.key)}
                                                />
                                                {action.label}
                                            </label>
                                        ))}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="flex items-center gap-2">
                        <AdminButton type="submit" isLoading={processing}>
                            Save changes
                        </AdminButton>
                        <AdminButton type="button" variant="neutral" onClick={() => router.visit('/admin/staff/roles')}>
                            Cancel
                        </AdminButton>
                    </div>
                </form>

                <div className="lg:sticky lg:top-6">
                    <RolePreviewCard name={role.name} selectedPermissionKeys={data.permissions} catalog={permissionCatalog} />
                </div>
            </div>
        </AdminShell>
    );
}
