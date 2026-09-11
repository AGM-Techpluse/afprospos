import AdminBadge from './AdminBadge';
import Icon from '../Icons/Icon';

type PermissionAction = { key: string; label: string };
type PermissionModule = { module: string; actions: PermissionAction[] };

interface RolePreviewCardProps {
    name: string;
    selectedPermissionKeys: string[];
    catalog: PermissionModule[];
}

/** Live preview of what this role will grant, built from the form's own state as permissions are checked. */
export default function RolePreviewCard({ name, selectedPermissionKeys, catalog }: RolePreviewCardProps) {
    const hasName = name.trim().length > 0;
    const grantedModules = catalog
        .map((mod) => ({
            module: mod.module,
            actions: mod.actions.filter((action) => selectedPermissionKeys.includes(action.key)),
        }))
        .filter((mod) => mod.actions.length > 0);

    return (
        <div className="bg-admin-surface border border-admin-border rounded-admin-card p-5">
            <p className="text-xs font-semibold text-admin-text3 uppercase tracking-wide mb-4">Preview</p>

            <div className="flex items-center gap-3 mb-5">
                <div className="h-12 w-12 rounded-full bg-admin-blue-soft text-admin-blue flex items-center justify-center text-lg shrink-0">
                    <Icon name="person-badge" />
                </div>
                <p className={`font-semibold truncate ${hasName ? 'text-admin-text' : 'text-admin-text3 italic'}`}>
                    {hasName ? name : 'New role'}
                </p>
            </div>

            {grantedModules.length === 0 ? (
                <p className="text-admin-text3 text-sm">No permissions selected yet.</p>
            ) : (
                <div className="space-y-3">
                    {grantedModules.map((mod) => (
                        <div key={mod.module}>
                            <p className="text-xs font-semibold text-admin-text2 mb-1.5">{mod.module}</p>
                            <div className="flex flex-wrap gap-1.5">
                                {mod.actions.map((action) => (
                                    <AdminBadge key={action.key} status="info">
                                        {action.label}
                                    </AdminBadge>
                                ))}
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}
