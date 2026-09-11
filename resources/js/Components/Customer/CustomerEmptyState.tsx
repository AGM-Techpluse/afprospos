import { ReactNode } from 'react';
import Icon from '../Icons/Icon';

interface CustomerEmptyStateProps {
    icon: string;
    description: string;
    action?: ReactNode;
    className?: string;
}

/** UI/UX §24 (Customer) — friendly icon, short explanation, single obvious action. */
export default function CustomerEmptyState({ icon, description, action, className = '' }: CustomerEmptyStateProps) {
    return (
        <div className={`flex flex-col items-center justify-center text-center py-8 px-4 ${className}`}>
            <div className="w-12 h-12 rounded-customer-pill bg-white shadow-customer-soft flex items-center justify-center text-customer-blue text-xl mb-3">
                <Icon name={icon} />
            </div>
            <p className="text-customer-text2 text-customer-caption">{description}</p>
            {action && <div className="mt-4">{action}</div>}
        </div>
    );
}
