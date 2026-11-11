import { useEffect, useState } from 'react';
import Icon from '../Icons/Icon';
import { ToastItem, ToastKind, useToastQueue } from '../../hooks/useToast';

const KIND_ICON: Record<ToastKind, string> = {
    info: 'info-circle',
    success: 'check-circle',
    warning: 'exclamation-triangle',
    danger: 'x-circle',
};

/** Soft full-background "glow" per kind (Cloudflare-style flash message) — border/text/icon-circle colors all come from the same family so each toast reads as one tinted surface, not a white card with an accent stripe. */
const KIND_GLOW_BG: Record<ToastKind, string> = {
    info: 'bg-admin-blue-soft',
    success: 'bg-admin-green-soft',
    warning: 'bg-admin-yellow-soft',
    danger: 'bg-admin-red-soft',
};

const KIND_GLOW_BORDER: Record<ToastKind, string> = {
    info: 'border-admin-border',
    success: 'border-admin-border-success',
    warning: 'border-admin-border-warning',
    danger: 'border-admin-border-danger',
};

const KIND_GLOW_TEXT: Record<ToastKind, string> = {
    info: 'text-admin-blue',
    success: 'text-admin-green',
    warning: 'text-admin-yellow-text',
    danger: 'text-admin-red',
};

const KIND_ICON_CIRCLE_BG: Record<ToastKind, string> = {
    info: 'bg-admin-blue',
    success: 'bg-admin-green',
    warning: 'bg-admin-yellow-text',
    danger: 'bg-admin-red',
};

/** UI/UX §12.5 — top-right, soft-tinted "glow" card (Cloudflare-style), colored per kind. */
export function AdminToastViewport() {
    const { toasts, dismiss } = useToastQueue();

    // left-4 (cancelled again at sm:) gives mobile an even gutter on both sides — right-4 alone with
    // w-full/max-w-sm has no left bound below max-w-sm's 384px, so it ran edge-to-edge (and slightly
    // past the left edge) on anything narrower than that.
    return (
        <div className="fixed top-4 left-4 right-4 sm:left-auto z-50 flex flex-col gap-2 sm:w-full sm:max-w-sm" aria-live="polite">
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
                flex items-start gap-2.5 border rounded-admin-card p-3.5 shadow-admin-modal
                transition-all duration-300 ease-standard
                ${KIND_GLOW_BG[toast.kind]} ${KIND_GLOW_BORDER[toast.kind]}
                ${mounted ? 'opacity-100 translate-x-0' : 'opacity-0 translate-x-4'}
            `}
        >
            <span className={`shrink-0 w-5 h-5 rounded-full flex items-center justify-center mt-0.5 ${KIND_ICON_CIRCLE_BG[toast.kind]}`}>
                <Icon name={KIND_ICON[toast.kind]} className="text-white text-[11px]" />
            </span>
            <div className="flex-1 min-w-0">
                <div className={`text-sm font-semibold ${KIND_GLOW_TEXT[toast.kind]}`}>{toast.title}</div>
                {toast.message && <div className={`text-xs mt-0.5 opacity-90 ${KIND_GLOW_TEXT[toast.kind]}`}>{toast.message}</div>}
            </div>
            <button type="button" onClick={onDismiss} aria-label="Dismiss" className={`shrink-0 opacity-70 hover:opacity-100 ${KIND_GLOW_TEXT[toast.kind]}`}>
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
