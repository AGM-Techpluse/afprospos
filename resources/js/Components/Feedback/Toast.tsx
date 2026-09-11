import { useEffect, useState } from 'react';
import Icon from '../Icons/Icon';
import { ToastItem, ToastKind, useToastQueue } from '../../hooks/useToast';

const KIND_ICON: Record<ToastKind, string> = {
    info: 'info-circle',
    success: 'check-circle',
    warning: 'exclamation-triangle',
    danger: 'x-circle',
};

const KIND_BORDER: Record<ToastKind, string> = {
    info: 'border-l-admin-blue',
    success: 'border-l-admin-green',
    warning: 'border-l-admin-yellow',
    danger: 'border-l-admin-red',
};

const KIND_ICON_COLOR: Record<ToastKind, string> = {
    info: 'text-ui-info',
    success: 'text-ui-success',
    warning: 'text-ui-warning-text',
    danger: 'text-ui-danger',
};

/** UI/UX §12.5 — top-right, small rectangular white card, 4px colored left border. */
export function AdminToastViewport() {
    const { toasts, dismiss } = useToastQueue();

    return (
        <div className="fixed top-4 right-4 z-50 flex flex-col gap-2 w-full max-w-sm" aria-live="polite">
            {toasts.map((toast) => (
                <AdminToastCard key={toast.id} toast={toast} onDismiss={() => dismiss(toast.id)} />
            ))}
        </div>
    );
}

function AdminToastCard({ toast, onDismiss }: { toast: ToastItem; onDismiss: () => void }) {
    const mounted = useMountedTransition();

    return (
        <div
            role="status"
            className={`
                flex items-start gap-2 bg-ui-surface border-l-4 rounded-admin-button p-3 shadow-admin-modal
                transition-all duration-300 ease-standard
                ${KIND_BORDER[toast.kind]}
                ${mounted ? 'opacity-100 translate-x-0' : 'opacity-0 translate-x-4'}
            `}
        >
            <Icon name={KIND_ICON[toast.kind]} className={`mt-0.5 ${KIND_ICON_COLOR[toast.kind]}`} />
            <div className="flex-1 min-w-0">
                <div className="text-sm font-semibold text-admin-text">{toast.title}</div>
                {toast.message && <div className="text-xs text-admin-text2 mt-0.5">{toast.message}</div>}
            </div>
            <button type="button" onClick={onDismiss} aria-label="Dismiss" className="text-admin-text3 hover:text-admin-text">
                <Icon name="x-lg" />
            </button>
        </div>
    );
}

const KIND_PILL_ICON_BG: Record<ToastKind, string> = {
    info: 'bg-customer-blue',
    success: 'bg-customer-green',
    warning: 'bg-customer-yellow',
    danger: 'bg-customer-red',
};

/** UI/UX §12.6 — bottom-center gradient pill, overlapping icon, bouncy spring entrance. This is a product signature and must keep the spring easing, not a linear/standard one. */
export function CustomerToastViewport() {
    const { toasts, dismiss } = useToastQueue();

    return (
        <div className="fixed bottom-4 inset-x-0 z-50 flex flex-col items-center gap-2" aria-live="polite">
            {toasts.map((toast) => (
                <CustomerToastPill key={toast.id} toast={toast} onDismiss={() => dismiss(toast.id)} />
            ))}
        </div>
    );
}

function CustomerToastPill({ toast, onDismiss }: { toast: ToastItem; onDismiss: () => void }) {
    const mounted = useMountedTransition();

    return (
        <div
            role="status"
            onClick={onDismiss}
            className={`
                relative flex items-center gap-3 bg-customer-hero-gradient text-white
                rounded-customer-pill shadow-customer-strong pl-12 pr-4 py-3
                w-7/8 max-w-ui-toast cursor-pointer
                transition-all duration-300 ease-spring
                ${mounted ? 'opacity-100 translate-y-0 scale-100' : 'opacity-0 translate-y-4 scale-95'}
            `}
        >
            <div className={`absolute -left-1 top-1/2 -translate-y-1/2 h-10 w-10 rounded-customer-pill flex items-center justify-center shadow-customer-soft ${KIND_PILL_ICON_BG[toast.kind]}`}>
                <Icon name={KIND_ICON[toast.kind]} className="text-white" />
            </div>
            <div className="flex-1 min-w-0">
                <div className="font-bold text-customer-base">{toast.title}</div>
                {toast.message && <div className="text-customer-caption opacity-90">{toast.message}</div>}
            </div>
        </div>
    );
}

function useMountedTransition(): boolean {
    const [mounted, setMounted] = useState(false);
    useEffect(() => {
        const frame = requestAnimationFrame(() => setMounted(true));
        return () => cancelAnimationFrame(frame);
    }, []);
    return mounted;
}
