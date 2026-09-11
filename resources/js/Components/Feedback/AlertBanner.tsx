import { ReactNode } from 'react';
import Icon from '../Icons/Icon';

export type AlertBannerKind = 'info' | 'success' | 'warning' | 'danger';

interface AlertBannerProps {
    kind: AlertBannerKind;
    children: ReactNode;
    action?: ReactNode;
    onDismiss?: () => void;
    className?: string;
}

const KIND_CONFIG: Record<AlertBannerKind, { icon: string; bg: string; text: string }> = {
    info: { icon: 'info-circle', bg: 'bg-ui-info-soft', text: 'text-ui-info' },
    success: { icon: 'check-circle', bg: 'bg-ui-success-soft', text: 'text-ui-success' },
    warning: { icon: 'exclamation-triangle', bg: 'bg-ui-warning-soft', text: 'text-ui-warning-text' },
    danger: { icon: 'x-circle', bg: 'bg-ui-danger-soft', text: 'text-ui-danger' },
};

/**
 * UI/UX §10.5 (General Server/Mismatch banner) + §12.7 — for persistent or
 * contextual attention, not momentary events (use Toast for those).
 * Theme-agnostic: consumes --ui-* so it renders correctly under either
 * [data-theme] ancestor without a JS theme prop.
 */
export default function AlertBanner({ kind, children, action, onDismiss, className = '' }: AlertBannerProps) {
    const config = KIND_CONFIG[kind];

    return (
        <div
            role={kind === 'danger' || kind === 'warning' ? 'alert' : 'status'}
            className={`flex items-start gap-3 rounded-admin-card p-4 ${config.bg} ${config.text} ${className}`}
        >
            <Icon name={config.icon} className="mt-0.5 shrink-0" />
            <div className="flex-1 text-sm">{children}</div>
            {action && <div className="shrink-0">{action}</div>}
            {onDismiss && (
                <button
                    type="button"
                    onClick={onDismiss}
                    aria-label="Dismiss"
                    className={`shrink-0 ${config.text} hover:opacity-70`}
                >
                    <Icon name="x-lg" />
                </button>
            )}
        </div>
    );
}
