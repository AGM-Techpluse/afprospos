import { usePage } from '@inertiajs/react';

interface BlockingLoaderProps {
    show: boolean;
    label?: string;
}

/**
 * UI/UX §13.2/§13.3 — full-screen blocking wait: frosted overlay + the
 * rotating AfProsPos site mark, not a generic spinner (generic spinners are
 * for button-level waits — see AdminButton/CustomerButton isLoading).
 * The caller owns the async state; this is a plain presentational component.
 */
export default function BlockingLoader({ show, label = 'Loading…' }: BlockingLoaderProps) {
    const { app_name, app_logo } = usePage().props as any;

    if (!show) return null;

    return (
        <div
            className="fixed inset-0 z-50 flex flex-col items-center justify-center gap-4 bg-ui-surface/80 backdrop-blur-ui-loader"
            role="status"
            aria-live="polite"
        >
            <img src={app_logo} alt={app_name} className="w-ui-loader h-ui-loader animate-spin" />
            <div className="text-sm font-medium text-ui-text">{label}</div>
        </div>
    );
}
