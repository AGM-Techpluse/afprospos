import { Head, router } from '@inertiajs/react';
import AdminShell from '../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../Components/Admin/AdminPageHead';
import AdminButton from '../../../../Components/Admin/AdminButton';

type RoleModule = { module: string; actions: string };
type Role = { name: string; modules: RoleModule[] };

export default function RolesIndex({ roles }: { roles: Role[] }) {
    return (
        <AdminShell>
            <Head title="Roles" />

            <AdminPageHead
                title="Roles"
                description="Manage roles and the permissions each one grants."
                actions={<AdminButton onClick={() => router.visit('/admin/staff/roles/create')}>New role</AdminButton>}
            />

            <div className="flex flex-col gap-4">
                {roles.map((role) => (
                    <div key={role.name} className="bg-admin-surface border border-admin-border rounded-admin-card p-6">
                        <div className="flex items-center justify-between mb-3">
                            <h2 className="text-admin-md font-semibold text-admin-text">{role.name}</h2>
                            <AdminButton
                                variant="neutral"
                                onClick={() => router.visit(`/admin/staff/roles/${encodeURIComponent(role.name)}/edit`)}
                            >
                                Edit
                            </AdminButton>
                        </div>
                        <table className="w-full text-sm">
                            <tbody className="divide-y divide-admin-border">
                                {role.modules.length === 0 && (
                                    <tr>
                                        <td className="py-1.5 text-admin-text2">No permissions granted.</td>
                                    </tr>
                                )}
                                {role.modules.map((entry) => (
                                    <tr key={entry.module}>
                                        <td className="py-1.5 pr-4 text-admin-text2">{entry.module}</td>
                                        <td className="py-1.5 text-admin-text">{entry.actions}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                ))}
            </div>
        </AdminShell>
    );
}
