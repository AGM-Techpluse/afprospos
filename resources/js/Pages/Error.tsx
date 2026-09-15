import { Head } from '@inertiajs/react';
import Icon from '../Components/Icons/Icon';

interface ErrorPageProps {
    status: number;
    theme: 'admin' | 'customer';
}

const COPY: Record<number, { icon: string; title: string; description: string }> = {
    403: {
        icon: 'shield-lock',
        title: "You don't have access to this",
        description: "This page needs a permission your account doesn't have yet. If that seems wrong, ask your shop owner or admin to check your role.",
    },
    404: {
        icon: 'compass',
        title: "We couldn't find that page",
        description: "It may have moved, or the link might be off. Let's get you back on track.",
    },
    419: {
        icon: 'hourglass-split',
        title: 'Your session timed out',
        description: 'That happens after a while for your security. Refresh the page and pick up where you left off.',
    },
    429: {
        icon: 'speedometer2',
        title: 'Slow down a little',
        description: "You've made a few too many requests in a short time. Give it a moment and try again.",
    },
    500: {
        icon: 'exclamation-octagon',
        title: 'Something went wrong on our end',
        description: "This one's on us, not you — we're looking into it. Please try again shortly.",
    },
    503: {
        icon: 'tools',
        title: "We're down for a moment",
        description: 'A bit of maintenance is underway. We should be back very soon.',
    },
};

const FALLBACK = {
    icon: 'exclamation-triangle',
    title: 'Something unexpected happened',
    description: 'Please try again, or head back to somewhere familiar.',
};

export default function ErrorPage({ status, theme }: ErrorPageProps) {
    const copy = COPY[status] ?? FALLBACK;
    const isAdmin = theme === 'admin';
    const homeHref = isAdmin ? '/admin/dashboard' : '/customer/dashboard';
    const homeLabel = isAdmin ? 'Back to dashboard' : 'Back home';

    return (
        <div
            data-theme={theme}
            className={isAdmin ? 'min-h-screen bg-admin-bg flex items-center justify-center p-4' : 'min-h-screen bg-white flex items-center justify-center p-4'}
        >
            <Head title={`${status} — ${copy.title}`} />

            <div
                className={
                    isAdmin
                        ? 'w-full max-w-md bg-admin-surface border border-admin-border rounded-admin-card p-8 text-center'
                        : 'w-full max-w-md bg-white rounded-customer-card shadow-customer-soft p-8 text-center'
                }
            >
                <div
                    className={
                        isAdmin
                            ? 'w-16 h-16 rounded-full bg-admin-red-soft text-admin-red flex items-center justify-center mx-auto mb-5 text-3xl'
                            : 'w-16 h-16 rounded-customer-pill bg-customer-red-soft text-customer-red flex items-center justify-center mx-auto mb-5 text-3xl'
                    }
                >
                    <Icon name={copy.icon} />
                </div>

                <div className={isAdmin ? 'text-xs font-bold tracking-widest uppercase text-admin-text3 mb-2' : 'text-xs font-bold tracking-widest uppercase text-customer-text2 mb-2'}>
                    Error {status}
                </div>

                <h1 className={isAdmin ? 'text-admin-xl font-bold text-admin-text mb-2' : 'text-customer-lg font-bold text-customer-text mb-2'}>
                    {copy.title}
                </h1>

                <p className={isAdmin ? 'text-sm text-admin-text2 mb-6' : 'text-customer-caption text-customer-text2 mb-6'}>{copy.description}</p>

                <div className="flex items-center justify-center gap-2">
                    <button
                        type="button"
                        onClick={() => window.history.back()}
                        className={
                            isAdmin
                                ? 'rounded-admin-button text-admin-button font-semibold px-admin-btn-x py-admin-btn-y bg-white border border-admin-border text-admin-text hover:bg-admin-hover'
                                : 'rounded-customer-pill text-customer-caption font-semibold px-5 py-2.5 bg-white border border-customer-input-border text-customer-text'
                        }
                    >
                        Go back
                    </button>
                    <a
                        href={homeHref}
                        className={
                            isAdmin
                                ? 'rounded-admin-button text-admin-button font-semibold px-admin-btn-x py-admin-btn-y bg-admin-blue text-white hover:bg-blue-700'
                                : 'rounded-customer-pill text-customer-caption font-semibold px-5 py-2.5 bg-customer-blue text-white'
                        }
                    >
                        {homeLabel}
                    </a>
                </div>
            </div>
        </div>
    );
}
