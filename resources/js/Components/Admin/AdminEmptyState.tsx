import { ReactNode } from 'react';
import Icon from '../Icons/Icon';

interface AdminEmptyStateProps {
    icon: string;
    title: string;
    description?: string;
    action?: ReactNode;
}

/** UI/UX §24 (Admin) — neutral icon, short title, one-line explanation, optional action. Kept compact. */
export default function AdminEmptyState({ icon, title, description, action }: AdminEmptyStateProps) {
    return (
        <div className="flex flex-col items-center justify-center text-center py-10 px-4">
            <div className="w-10 h-10 rounded-admin-card bg-admin-hover flex items-center justify-center text-admin-text3 text-lg mb-3">
                <Icon name={icon} />
            </div>
            <h3 className="text-sm font-semibold text-admin-text">{title}</h3>
            {description && <p className="text-admin-text2 text-xs mt-1 max-w-xs">{description}</p>}
            {action && <div className="mt-4">{action}</div>}
        </div>
    );
}
