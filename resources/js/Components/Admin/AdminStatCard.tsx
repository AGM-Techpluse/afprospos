import { Link } from '@inertiajs/react';

interface AdminStatCardProps {
    label: string;
    value: string;
    /** Signed delta text, e.g. "+12%" / "-3%" — color follows sign (UI/UX §3.4: green = positive delta). */
    delta?: string;
    deltaDirection?: 'up' | 'down';
    /** 'blue' is a solid-fill emphasis treatment for a small cluster of headline numbers (e.g. Shop analytics) — 'default' stays flat/bordered per §6.1. */
    variant?: 'default' | 'blue';
    /** Makes the whole card an Inertia link (e.g. Staff -> the staff list filtered to this shop) — renders as a plain div when omitted. */
    href?: string;
    /** Stretch to fill a taller parent (e.g. matching a sibling card's height) rather than sizing to content. */
    fillHeight?: boolean;
}

/** UI/UX §6.1 — flat, border-driven, no shadow by default (§14B.8's dashboard grid: spans 3 columns desktop). */
export default function AdminStatCard({ label, value, delta, deltaDirection, variant = 'default', href, fillHeight = false }: AdminStatCardProps) {
    const isBlue = variant === 'blue';

    const className = `flex flex-col justify-center p-4 ${fillHeight ? 'h-full' : ''} ${
        isBlue ? 'bg-admin-blue rounded-admin-card' : 'bg-admin-surface border border-admin-border rounded-admin-card'
    } ${href ? 'transition-transform hover:scale-[1.02] cursor-pointer' : ''}`;

    const content = (
        <>
            <div className={`text-xs font-semibold uppercase tracking-wide ${isBlue ? 'text-white/80' : 'text-admin-text2'}`}>
                {label}
            </div>
            <div className={`text-admin-xl font-bold mt-1 ${isBlue ? 'text-white' : 'text-admin-text'}`}>{value}</div>
            {delta && (
                <div
                    className={`text-xs font-semibold mt-1 ${
                        isBlue ? 'text-white' : deltaDirection === 'down' ? 'text-admin-red' : 'text-admin-green'
                    }`}
                >
                    {delta}
                </div>
            )}
        </>
    );

    if (href) {
        return (
            <Link href={href} className={className}>
                {content}
            </Link>
        );
    }

    return <div className={className}>{content}</div>;
}
