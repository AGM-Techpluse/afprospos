import { ReactNode } from 'react';
import Icon from '../Icons/Icon';

interface CustomerDetailCardDetail {
    label: string;
    value: ReactNode;
}

type BadgeTone = 'success' | 'warning' | 'danger' | 'info';

interface CustomerDetailCardBadge {
    label: string;
    tone?: BadgeTone;
}

interface CustomerDetailCardProps {
    icon: string;
    title: string;
    subtitle?: string;
    badge?: CustomerDetailCardBadge;
    /** Rendered as a tinted key/value grid — the equivalent of the reference travel-booking app's Check In / Check Out / Guests / Rooms block. */
    details?: CustomerDetailCardDetail[];
    amountLabel?: string;
    amount?: string;
    actions?: ReactNode;
}

const BADGE_TONE_CLASS: Record<BadgeTone, string> = {
    success: 'bg-customer-green-soft text-customer-green',
    warning: 'bg-customer-yellow-soft text-customer-yellow-text',
    danger: 'bg-customer-red-soft text-customer-red',
    info: 'bg-customer-blue-soft text-customer-blue',
};

/** A single record's full detail card (Repair/Order/Warranty-claim Show pages) — matches the reference travel-booking app's hotel-booking-detail card: icon, status badge, a tinted key/value grid, then amount + actions below a divider. */
export default function CustomerDetailCard({ icon, title, subtitle, badge, details, amountLabel, amount, actions }: CustomerDetailCardProps) {
    return (
        <div className="bg-white rounded-customer-card shadow-customer-soft p-4">
            <div className="flex items-start justify-between gap-3 mb-3">
                <div className="flex items-center gap-3 min-w-0">
                    <div className="w-11 h-11 rounded-customer-item bg-customer-blue-soft text-customer-blue flex items-center justify-center shrink-0 text-lg">
                        <Icon name={icon} />
                    </div>
                    <div className="min-w-0">
                        <div className="font-bold text-customer-base text-customer-text truncate">{title}</div>
                        {subtitle && <div className="text-customer-caption text-customer-text2 truncate">{subtitle}</div>}
                    </div>
                </div>
                {badge && (
                    <span className={`shrink-0 rounded-customer-pill px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide ${BADGE_TONE_CLASS[badge.tone ?? 'info']}`}>
                        {badge.label}
                    </span>
                )}
            </div>

            {details && details.length > 0 && (
                <div className="grid grid-cols-2 gap-x-3 gap-y-2 bg-customer-blue-soft/40 rounded-customer-item px-3 py-2.5 mb-3">
                    {details.map((detail) => (
                        <div key={detail.label}>
                            <div className="text-[10px] uppercase tracking-wide text-customer-text2">{detail.label}</div>
                            <div className="font-semibold text-customer-caption text-customer-text">{detail.value}</div>
                        </div>
                    ))}
                </div>
            )}

            {(amount || actions) && (
                <div className="flex items-center justify-between gap-3 flex-wrap pt-3 border-t border-gray-100">
                    <div>
                        {amount && (
                            <>
                                {amountLabel && <div className="text-[10px] uppercase tracking-wide text-customer-text2">{amountLabel}</div>}
                                <div className="font-bold text-customer-base text-customer-text">{amount}</div>
                            </>
                        )}
                    </div>
                    {actions && <div className="flex items-center gap-2 flex-wrap">{actions}</div>}
                </div>
            )}
        </div>
    );
}
