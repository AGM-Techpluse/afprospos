import React, { createContext, useCallback, useContext, useMemo, useRef, useState } from 'react';
import type { ConfirmKind } from '../Components/Feedback/ConfirmDialog';

export interface ConfirmOptions {
    kind?: ConfirmKind;
    title: string;
    body?: React.ReactNode;
    confirmLabel?: string;
    cancelLabel?: string;
    dismissible?: boolean;
}

export interface ConfirmDialogState {
    open: boolean;
    kind?: ConfirmKind;
    title: string;
    body?: React.ReactNode;
    confirmLabel?: string;
    cancelLabel?: string;
    dismissible?: boolean;
    onConfirm: () => void;
    onCancel: () => void;
}

type ConfirmFn = (options: ConfirmOptions) => Promise<boolean>;

const ConfirmContext = createContext<ConfirmFn | null>(null);
const ConfirmDialogStateContext = createContext<ConfirmDialogState | null>(null);

/**
 * Mount once at the app root (resources/js/app.tsx). Only holds state/context here —
 * the dialog itself is rendered by ConfirmDialogHost inside each themed shell
 * (AdminShell/AdminAuthShell/CustomerShell/CustomerAuthShell), same split as
 * ToastProvider/AdminToastViewport/CustomerToastViewport. Rendering it here
 * instead put it outside any [data-theme] ancestor, so --ui-overlay/--ui-surface/
 * --ui-shadow-modal all resolved to nothing and the modal had no backdrop or surface.
 */
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

    const dialogState = useMemo<ConfirmDialogState>(() => ({
        open: options !== null,
        kind: options?.kind,
        title: options?.title ?? '',
        body: options?.body,
        confirmLabel: options?.confirmLabel,
        cancelLabel: options?.cancelLabel,
        dismissible: options?.dismissible,
        onConfirm: () => settle(true),
        onCancel: () => settle(false),
    }), [options, settle]);

    return React.createElement(
        ConfirmContext.Provider,
        { value: confirm },
        React.createElement(ConfirmDialogStateContext.Provider, { value: dialogState }, children)
    );
}

export function useConfirm(): ConfirmFn {
    const confirm = useContext(ConfirmContext);
    if (!confirm) {
        throw new Error('useConfirm must be used within a ConfirmProvider');
    }
    return confirm;
}

/** Internal — consumed by ConfirmDialogHost only. */
export function useConfirmDialogState(): ConfirmDialogState {
    const state = useContext(ConfirmDialogStateContext);
    if (!state) {
        throw new Error('useConfirmDialogState must be used within a ConfirmProvider');
    }
    return state;
}
