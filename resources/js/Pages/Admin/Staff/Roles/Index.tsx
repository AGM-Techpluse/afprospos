import { Head, router } from '@inertiajs/react';
import AdminShell from '../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../Components/Admin/AdminPageHead';
import AdminButton from '../../../../Components/Admin/AdminButton';
import AdminInfoCard from '../../../../Components/Admin/AdminInfoCard';

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

            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                {roles.map((role) => (
                    <AdminInfoCard
                        key={role.name}
                        title={role.name}
                        icon="person-badge"
                        className="h-[380px]"
                        actions={
                            <AdminButton
                                variant="neutral"
                                onClick={() => router.visit(`/admin/staff/roles/${encodeURIComponent(role.name)}/edit`)}
                            >
                                Edit
                            </AdminButton>
                        }
                    >
                        {role.modules.length === 0 ? (
                            <p className="text-sm text-admin-text2">No permissions granted.</p>
                        ) : (
                            <ul className="divide-y divide-admin-border -my-1">
                                {role.modules.map((entry) => (
                                    <li key={entry.module} className="flex items-center justify-between py-2 text-sm">
                                        <span className="text-admin-text2">{entry.module}</span>
                                        <span className="text-admin-text font-medium">{entry.actions}</span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </AdminInfoCard>
                ))}
            </div>
        </AdminShell>
    );
}
