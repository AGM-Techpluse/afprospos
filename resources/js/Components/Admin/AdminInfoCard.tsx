import { ReactNode } from 'react';
import Icon from '../Icons/Icon';

type IconColor = 'blue' | 'green' | 'yellow' | 'red' | 'neutral';

const ICON_COLOR_CLASSES: Record<IconColor, string> = {
    blue: 'bg-admin-blue-soft text-admin-blue',
    green: 'bg-admin-green-soft text-admin-green',
    yellow: 'bg-admin-yellow-soft text-admin-yellow-text',
    red: 'bg-admin-red-soft text-admin-red',
    neutral: 'bg-admin-hover text-admin-text2',
};

interface AdminInfoCardProps {
    title: string;
    /** Bootstrap Icons name for the header's icon chip — omit for a plain text-only header. */
    icon?: string;
    iconColor?: IconColor;
    description?: string;
    /** Right-aligned header slot — an Edit button, a badge, etc. */
    actions?: ReactNode;
    children: ReactNode;
    className?: string;
}

/**
 * The shared "info panel" shape for read-only/display content (a
 * detail view, a role's permission grid, a stock breakdown) — distinct
 * from a `<form>` container, which CLAUDE.md's Invariant #19 forbids
 * wrapping in a card. A soft shadow + icon-chip header replaces the
 * flat border-only box every one of these panels used to hand-roll
 * (`bg-admin-surface border border-admin-border rounded-admin-card p-6`)
 * with no visual hierarchy between the title and the border around it.
 */
export default function AdminInfoCard({ title, icon, iconColor = 'blue', description, actions, children, className = '' }: AdminInfoCardProps) {
    return (
        <div className={`flex flex-col bg-admin-surface border border-admin-border rounded-admin-modal shadow-admin-card overflow-hidden ${className}`}>
            <div className="flex items-center gap-3 px-6 py-4 border-b border-admin-border shrink-0">
                {icon && (
                    <span
                        className={`w-9 h-9 rounded-admin-button flex items-center justify-center shrink-0 text-base ${ICON_COLOR_CLASSES[iconColor]}`}
                    >
                        <Icon name={icon} />
                    </span>
                )}
                <div className="flex-1 min-w-0">
                    <h2 className="text-admin-md font-semibold text-admin-text truncate">{title}</h2>
                    {description && <p className="text-xs text-admin-text3 mt-0.5">{description}</p>}
                </div>
                {actions && <div className="shrink-0">{actions}</div>}
            </div>
            <div className="flex-1 min-h-0 overflow-y-auto p-6">{children}</div>
        </div>
    );
}
