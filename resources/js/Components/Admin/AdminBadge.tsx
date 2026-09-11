import { ReactNode } from 'react';

export type AdminBadgeStatus = 'info' | 'success' | 'warning' | 'danger' | 'neutral';

const STATUS_CLASSES: Record<AdminBadgeStatus, string> = {
    info: 'bg-admin-blue-soft text-admin-blue',
    success: 'bg-admin-green-soft text-admin-green',
    warning: 'bg-admin-yellow-soft text-admin-yellow-text',
    danger: 'bg-admin-red-soft text-admin-red',
    neutral: 'bg-admin-hover text-admin-text2',
};

interface AdminBadgeProps {
    status: AdminBadgeStatus;
    children: ReactNode;
}

/** UI/UX §9.2/§22 status contract — dot + label, never color alone. */
export default function AdminBadge({ status, children }: AdminBadgeProps) {
    return (
        <span className={`inline-flex items-center gap-1.5 rounded-admin-badge px-2 py-0.5 text-xs font-semibold ${STATUS_CLASSES[status]}`}>
            <span className="w-1.5 h-1.5 rounded-full bg-current" aria-hidden="true" />
            {children}
        </span>
    );
}
