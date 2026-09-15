import { useId } from 'react';
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
    const patternId = useId().replace(/:/g, '');

    const className = `relative overflow-hidden flex flex-col justify-center p-4 ${fillHeight ? 'h-full' : ''} ${
        isBlue ? 'bg-admin-blue rounded-admin-card' : 'bg-admin-surface border border-admin-border rounded-admin-card'
    } ${href ? 'transition-transform hover:scale-[1.02] cursor-pointer' : ''}`;

    const content = (
        <>
            {isBlue && (
                <svg className="absolute bottom-0 right-0 w-24 h-24 pointer-events-none" aria-hidden="true">
                    <defs>
                        <pattern id={`stat-pattern-${patternId}`} width="14" height="14" patternUnits="userSpaceOnUse" patternTransform="rotate(45)">
                            <polygon points="0,14 7,0 14,14" fill="white" fillOpacity="0.16" />
                        </pattern>
                        <clipPath id={`stat-clip-${patternId}`}>
                            <polygon points="96,96 96,26 26,96" />
                        </clipPath>
                    </defs>
                    <rect width="96" height="96" fill={`url(#stat-pattern-${patternId})`} clipPath={`url(#stat-clip-${patternId})`} />
                </svg>
            )}
            <div className={`relative text-xs font-semibold uppercase tracking-wide ${isBlue ? 'text-white/80' : 'text-admin-text2'}`}>
                {label}
            </div>
            <div className={`relative text-admin-xl font-bold mt-1 ${isBlue ? 'text-white' : 'text-admin-text'}`}>{value}</div>
            {delta && (
                <div
                    className={`relative text-xs font-semibold mt-1 ${
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
