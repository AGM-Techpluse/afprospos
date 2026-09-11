import AdminBadge from './AdminBadge';
import Icon from '../Icons/Icon';
import { initialsFor } from '../../lib/initials';

interface StaffPreviewCardProps {
    name: string;
    phone: string;
    email: string;
    /** Omit entirely on the Edit page (roles are managed on the Staff Show page instead). */
    roles?: string[];
    status?: 'active' | 'deactivated';
}

/** Live preview of how this staff member's profile will look, built from the form's own state as it's filled in. */
export default function StaffPreviewCard({ name, phone, email, roles, status }: StaffPreviewCardProps) {
    const hasName = name.trim().length > 0;

    return (
        <div className="bg-admin-surface border border-admin-border rounded-admin-card p-5">
            <p className="text-xs font-semibold text-admin-text3 uppercase tracking-wide mb-4">Preview</p>

            <div className="flex items-center gap-3 mb-5">
                <div className="h-12 w-12 rounded-full bg-admin-blue text-white font-bold flex items-center justify-center text-base shrink-0">
                    {hasName ? initialsFor(name) : <Icon name="person" />}
                </div>
                <div className="min-w-0">
                    <p className={`font-semibold truncate ${hasName ? 'text-admin-text' : 'text-admin-text3 italic'}`}>
                        {hasName ? name : 'New staff member'}
                    </p>
                    {status && (
                        <AdminBadge status={status === 'active' ? 'success' : 'neutral'}>
                            {status === 'active' ? 'Active' : 'Deactivated'}
                        </AdminBadge>
                    )}
                </div>
            </div>

            <dl className="space-y-2 text-sm mb-5">
                <div className="flex justify-between gap-2">
                    <dt className="text-admin-text2">Phone</dt>
                    <dd className="text-admin-text truncate">{phone || '—'}</dd>
                </div>
                <div className="flex justify-between gap-2">
                    <dt className="text-admin-text2">Email</dt>
                    <dd className="text-admin-text truncate">{email || '—'}</dd>
                </div>
            </dl>

            {roles !== undefined && (
                <>
                    <p className="text-xs font-semibold text-admin-text2 mb-2">Roles</p>
                    <div className="flex flex-wrap gap-1.5">
                        {roles.length === 0 && <span className="text-admin-text3 text-xs">No roles selected yet.</span>}
                        {roles.map((role) => (
                            <AdminBadge key={role} status="info">
                                {role}
                            </AdminBadge>
                        ))}
                    </div>
                </>
            )}
        </div>
    );
}
