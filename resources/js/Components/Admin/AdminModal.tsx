import React, { ReactNode, useEffect, useRef } from 'react';
import Icon from '../Icons/Icon';

interface AdminModalProps {
    open: boolean;
    title: string;
    description?: string;
    onClose: () => void;
    children: ReactNode;
    footer?: ReactNode;
    /** Unsafe-to-interrupt operations opt out of backdrop/Escape dismissal (UI/UX §12.4), same contract as ConfirmDialog. */
    dismissible?: boolean;
}

const FOCUSABLE_SELECTOR = 'a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])';

/**
 * The general-purpose sibling of ConfirmDialog (UI/UX §12.1-§12.4's modal
 * contract — overlay/surface/radius/shadow tokens, focus trap, Escape to
 * dismiss, mobile bottom sheet) for content ConfirmDialog's fixed
 * title+body+confirm/cancel shape can't hold — an arbitrary form body and
 * footer instead. Same overlay/surface tokens, wider by default since its
 * content is typically a list (e.g. a bulk action's per-row inputs), not a
 * one-line prompt.
 */
export default function AdminModal({ open, title, description, onClose, children, footer, dismissible = true }: AdminModalProps) {
    const dialogRef = useRef<HTMLDivElement>(null);
    const previouslyFocused = useRef<HTMLElement | null>(null);

    useEffect(() => {
        if (!open) return;

        previouslyFocused.current = document.activeElement as HTMLElement | null;
        dialogRef.current?.querySelector<HTMLElement>(FOCUSABLE_SELECTOR)?.focus();

        const handleKeyDown = (e: KeyboardEvent) => {
            if (e.key === 'Escape' && dismissible) {
                onClose();
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
    }, [open, dismissible, onClose]);

    if (!open) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center" role="presentation">
            <div className="absolute inset-0 bg-ui-overlay" onClick={() => dismissible && onClose()} aria-hidden="true" />

            <div
                ref={dialogRef}
                role="dialog"
                aria-modal="true"
                aria-labelledby="admin-modal-title"
                className="
                    relative bg-ui-surface w-full sm:w-full sm:max-w-lg
                    rounded-t-ui-sheet sm:rounded-admin-modal
                    fixed sm:relative bottom-0 sm:bottom-auto inset-x-0 sm:inset-x-auto
                    shadow-ui-modal max-h-[85vh] flex flex-col
                "
            >
                <div className="sm:hidden w-10 h-1 rounded-full bg-admin-border mx-auto mt-3" aria-hidden="true" />

                <div className="flex items-start justify-between gap-3 p-6 pb-4 shrink-0">
                    <div>
                        <h2 id="admin-modal-title" className="text-lg font-bold text-admin-text">{title}</h2>
                        {description && <p className="text-sm text-admin-text2 mt-1">{description}</p>}
                    </div>
                    <button
                        type="button"
                        onClick={onClose}
                        aria-label="Close"
                        className="text-admin-text3 hover:text-admin-text shrink-0"
                    >
                        <Icon name="x-lg" />
                    </button>
                </div>

                <div className="px-6 overflow-y-auto flex-1">{children}</div>

                {footer && <div className="p-6 pt-4 shrink-0">{footer}</div>}
            </div>
        </div>
    );
}
