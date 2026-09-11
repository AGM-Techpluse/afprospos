import React, { createContext, useCallback, useContext, useRef, useState } from 'react';
import ConfirmDialog, { ConfirmKind } from '../Components/Feedback/ConfirmDialog';

export interface ConfirmOptions {
    kind?: ConfirmKind;
    title: string;
    body?: React.ReactNode;
    confirmLabel?: string;
    cancelLabel?: string;
    dismissible?: boolean;
}

type ConfirmFn = (options: ConfirmOptions) => Promise<boolean>;

const ConfirmContext = createContext<ConfirmFn | null>(null);

/** Mount once at the app root (resources/js/app.tsx). Renders one shared ConfirmDialog (UI/UX §12.1) reactively — never call window.confirm(). */
export function ConfirmProvider({ children }: { children: React.ReactNode }) {
    const [options, setOptions] = useState<ConfirmOptions | null>(null);
    const resolver = useRef<((confirmed: boolean) => void) | null>(null);

    const confirm = useCallback<ConfirmFn>((next) => {
        setOptions(next);
        return new Promise<boolean>((resolve) => {
            resolver.current = resolve;
        });
    }, []);

    const settle = useCallback((confirmed: boolean) => {
        resolver.current?.(confirmed);
        resolver.current = null;
        setOptions(null);
    }, []);

    return React.createElement(
        ConfirmContext.Provider,
        { value: confirm },
        children,
        React.createElement(ConfirmDialog, {
            open: options !== null,
            kind: options?.kind,
            title: options?.title ?? '',
            body: options?.body,
            confirmLabel: options?.confirmLabel,
            cancelLabel: options?.cancelLabel,
            dismissible: options?.dismissible,
            onConfirm: () => settle(true),
            onCancel: () => settle(false),
        })
    );
}

export function useConfirm(): ConfirmFn {
    const confirm = useContext(ConfirmContext);
    if (!confirm) {
        throw new Error('useConfirm must be used within a ConfirmProvider');
    }
    return confirm;
}
