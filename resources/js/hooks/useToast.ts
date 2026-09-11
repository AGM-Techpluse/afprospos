import React, { createContext, useCallback, useContext, useMemo, useState } from 'react';

export type ToastKind = 'info' | 'success' | 'warning' | 'danger';

export interface ToastItem {
    id: string;
    kind: ToastKind;
    title: string;
    message?: string;
}

interface ToastContextValue {
    toasts: ToastItem[];
    dismiss: (id: string) => void;
}

/** UI/UX §12.5 — the prototype-demonstrated auto-dismiss duration, shared by both themes. */
export const TOAST_AUTO_DISMISS_MS = 3200;

const ToastContext = createContext<ToastContextValue | null>(null);
const ToastPushContext = createContext<((input: Omit<ToastItem, 'id'>) => void) | null>(null);

/** Mount once at the app root (resources/js/app.tsx). Rendering is handled separately by AdminToastViewport/CustomerToastViewport (Components/Feedback/Toast.tsx), whichever is mounted by the active theme's shell. */
export function ToastProvider({ children }: { children: React.ReactNode }) {
    const [toasts, setToasts] = useState<ToastItem[]>([]);

    const dismiss = useCallback((id: string) => {
        setToasts((current) => current.filter((toast) => toast.id !== id));
    }, []);

    const push = useCallback((input: Omit<ToastItem, 'id'>) => {
        const id = crypto.randomUUID();
        setToasts((current) => [...current, { ...input, id }]);
        window.setTimeout(() => dismiss(id), TOAST_AUTO_DISMISS_MS);
    }, [dismiss]);

    const contextValue = useMemo(() => ({ toasts, dismiss }), [toasts, dismiss]);

    // createElement (not JSX) so this stays a valid .ts file per the ADD's
    // hooks/ naming — ToastProvider is the one hook export that renders.
    return React.createElement(
        ToastPushContext.Provider,
        { value: push },
        React.createElement(ToastContext.Provider, { value: contextValue }, children)
    );
}

/** Internal — consumed by AdminToastViewport/CustomerToastViewport only. */
export function useToastQueue(): ToastContextValue {
    const context = useContext(ToastContext);
    if (!context) {
        throw new Error('useToastQueue must be used within a ToastProvider');
    }
    return context;
}

export function useToast() {
    const push = useContext(ToastPushContext);
    if (!push) {
        throw new Error('useToast must be used within a ToastProvider');
    }
    return {
        toast: (input: Omit<ToastItem, 'id'>) => push(input),
    };
}
