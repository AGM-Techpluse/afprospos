import React, { ReactNode, useEffect, useRef } from 'react';
import Icon from '../Icons/Icon';

export type ConfirmKind = 'danger' | 'warning' | 'info';

interface ConfirmDialogProps {
    open: boolean;
    kind?: ConfirmKind;
    title: string;
    body?: ReactNode;
    confirmLabel?: string;
    cancelLabel?: string;
    onConfirm: () => void;
    onCancel: () => void;
    /** Unsafe-to-interrupt operations opt out of backdrop/Escape dismissal (UI/UX §12.4). */
    dismissible?: boolean;
    loading?: boolean;
}

const KIND_STYLES: Record<ConfirmKind, { icon: string; iconClass: string; confirmClass: string }> = {
    danger: {
        icon: 'exclamation-triangle',
        iconClass: 'text-ui-danger bg-ui-danger-soft',
        confirmClass: 'bg-admin-red text-white hover:bg-red-700',
    },
    warning: {
        icon: 'exclamation-circle',
        iconClass: 'text-ui-warning-text bg-ui-warning-soft',
        confirmClass: 'bg-admin-yellow-soft text-admin-yellow-text hover:bg-yellow-200',
    },
    info: {
        icon: 'info-circle',
        iconClass: 'text-ui-info bg-ui-info-soft',
        confirmClass: 'bg-admin-blue text-white hover:bg-blue-700',
    },
};

const FOCUSABLE_SELECTOR = 'a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])';

/**
 * UI/UX §12.1-§12.4 — the one confirmation contract for both themes: desktop
 * centered modal (380px/14px radius) that becomes a mobile bottom sheet below
 * `sm`. Theme-agnostic — consumes --ui-overlay/--ui-shadow-modal so it
 * renders correctly under either [data-theme] ancestor without knowing which.
 */
export default function ConfirmDialog({
    open,
    kind = 'info',
    title,
    body,
    confirmLabel = 'Confirm',
    cancelLabel = 'Cancel',
    onConfirm,
    onCancel,
    dismissible = true,
    loading = false,
}: ConfirmDialogProps) {
    const dialogRef = useRef<HTMLDivElement>(null);
    const previouslyFocused = useRef<HTMLElement | null>(null);
    const styles = KIND_STYLES[kind];

    useEffect(() => {
        if (!open) return;

        previouslyFocused.current = document.activeElement as HTMLElement | null;

        const focusable = dialogRef.current?.querySelectorAll<HTMLElement>(FOCUSABLE_SELECTOR);
        const initial = kind === 'danger' ? findByText(focusable, cancelLabel) : findByText(focusable, confirmLabel);
        (initial ?? focusable?.[0])?.focus();

        const handleKeyDown = (e: KeyboardEvent) => {
            if (e.key === 'Escape' && dismissible && !loading) {
                onCancel();
                return;
            }

            if (e.key !== 'Tab') return;

            const nodes = dialogRef.current?.querySelectorAll<HTMLElement>(FOCUSABLE_SELECTOR);
            if (!nodes || nodes.length === 0) return;

            const first = nodes[0];
            const last = nodes[nodes.length - 1];

            if (e.shiftKey && document.activeElement === first) {
                e.preventDefault();
                last.focus();
            } else if (!e.shiftKey && document.activeElement === last) {
                e.preventDefault();
                first.focus();
            }
        };

        document.addEventListener('keydown', handleKeyDown);
        return () => {
            document.removeEventListener('keydown', handleKeyDown);
            previouslyFocused.current?.focus();
        };
    }, [open, kind, dismissible, loading, cancelLabel, confirmLabel, onCancel]);

    if (!open) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center sm:items-center" role="presentation">
            <div
                className="absolute inset-0 bg-ui-overlay"
                onClick={() => dismissible && !loading && onCancel()}
                aria-hidden="true"
            />

            <div
                ref={dialogRef}
                role="alertdialog"
                aria-modal="true"
                aria-labelledby="confirm-dialog-title"
                className="
                    relative bg-ui-surface w-full sm:w-ui-modal
                    rounded-t-ui-sheet sm:rounded-admin-modal
                    fixed sm:relative bottom-0 sm:bottom-auto inset-x-0 sm:inset-x-auto
                    shadow-ui-modal p-6
                "
            >
                <div className="sm:hidden w-10 h-1 rounded-full bg-admin-border mx-auto mb-4" aria-hidden="true" />

                <div className={`w-10 h-10 rounded-full flex items-center justify-center mb-4 ${styles.iconClass}`}>
                    <Icon name={styles.icon} className="text-lg" />
                </div>

                <h2 id="confirm-dialog-title" className="text-lg font-bold text-admin-text mb-2">
                    {title}
                </h2>
                {body && <div className="text-sm text-admin-text2 mb-6">{body}</div>}

                <div className="flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                    <button
                        type="button"
                        onClick={onCancel}
                        disabled={loading}
                        className="rounded-admin-button text-admin-button font-semibold px-admin-btn-x py-admin-btn-y bg-white border border-admin-border text-admin-text hover:bg-admin-hover disabled:opacity-60 disabled:cursor-not-allowed"
                    >
                        {cancelLabel}
                    </button>
                    <button
                        type="button"
                        onClick={onConfirm}
                        disabled={loading}
                        className={`rounded-admin-button text-admin-button font-semibold px-admin-btn-x py-admin-btn-y flex items-center justify-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed ${styles.confirmClass}`}
                    >
                        {loading && <Icon name="arrow-clockwise" className="animate-spin" />}
                        {loading ? 'Please wait…' : confirmLabel}
                    </button>
                </div>
            </div>
        </div>
    );
}

function findByText(nodes: NodeListOf<HTMLElement> | undefined, text: string): HTMLElement | undefined {
    if (!nodes) return undefined;
    return Array.from(nodes).find((node) => node.textContent?.trim() === text);
}
