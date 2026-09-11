import { ReactNode, useState } from 'react';
import Icon from '../Icons/Icon';

interface CustomerListItemDetail {
    label: string;
    value: ReactNode;
}

interface CustomerListItemProps {
    icon: string;
    title: string;
    subtitle: string;
    amount: string;
    details?: CustomerListItemDetail[];
    actions?: ReactNode;
}

/** UI/UX §14C.10 (Recent orders) — each item expands independently (the prototype does not restrict these to single-open, unlike the Admin row-card accordion in §9.6). */
export default function CustomerListItem({ icon, title, subtitle, amount, details, actions }: CustomerListItemProps) {
    const [open, setOpen] = useState(false);

    return (
        <div className="bg-white rounded-customer-item shadow-customer-soft mb-2 overflow-hidden">
            <button
                type="button"
                onClick={() => setOpen((current) => !current)}
                className="w-full flex items-center gap-3 p-3 text-left"
                aria-expanded={open}
            >
                <div className="w-9 h-9 rounded-customer-item bg-customer-blue-soft text-customer-blue flex items-center justify-center shrink-0">
                    <Icon name={icon} />
                </div>
                <div className="flex-1 min-w-0">
                    <div className="font-bold text-customer-base text-customer-text truncate">{title}</div>
                    <div className="text-customer-caption text-customer-text2">{subtitle}</div>
                </div>
                <div className="font-bold text-customer-base text-customer-text shrink-0">{amount}</div>
                <Icon
                    name="chevron-right"
                    className={`text-customer-muted-icon shrink-0 transition-transform duration-150 ${open ? 'rotate-90' : ''}`}
                />
            </button>

            {open && details && details.length > 0 && (
                <div className="px-4 pb-4">
                    {details.map((detail) => (
                        <div key={detail.label} className="flex items-center justify-between py-1.5 text-customer-caption">
                            <span className="text-customer-text2">{detail.label}</span>
                            <span className="font-semibold text-customer-text">{detail.value}</span>
                        </div>
                    ))}
                    {actions && <div className="flex items-center gap-2 mt-2">{actions}</div>}
                </div>
            )}
        </div>
    );
}
