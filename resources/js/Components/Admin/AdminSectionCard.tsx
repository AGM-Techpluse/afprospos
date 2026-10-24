import { ReactNode } from 'react';

interface AdminSectionCardProps {
    /** Omit for a plain bordered box with no header chrome (e.g. a mixed overview group that's self-describing without a title). */
    title?: string;
    /** Right-aligned header slot, e.g. a "View all" link — same pattern as the per-list headers this component replaces. */
    action?: ReactNode;
    children: ReactNode;
    className?: string;
}

/** Standard wrapper for a logical group of dashboard widgets — flat, border-driven per UI/UX §6.1, no shadow. */
export default function AdminSectionCard({ title, action, children, className = '' }: AdminSectionCardProps) {
    return (
        <div className={`bg-admin-surface border border-admin-border rounded-admin-card ${className}`}>
            {title && (
                <div className="flex items-center justify-between gap-3 px-5 py-3.5 border-b border-admin-border">
                    <h2 className="text-sm font-semibold text-admin-text">{title}</h2>
                    {action}
                </div>
            )}
            {children}
        </div>
    );
}
