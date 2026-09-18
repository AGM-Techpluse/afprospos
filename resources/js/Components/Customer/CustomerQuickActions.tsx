import { Link } from '@inertiajs/react';
import Icon from '../Icons/Icon';

export interface CustomerQuickAction {
    key: string;
    label: string;
    icon: string;
    /**
     * With the default (pill) layout: full solid color on mobile, softer
     * tint from `sm:` up — e.g. `'bg-customer-blue sm:bg-customer-blue-soft'`.
     * With `forceCardLayout`: a single unprefixed tint, e.g. `'bg-customer-blue-soft'`.
     * Either way, every variant must be literal text here (not built at
     * runtime) so Tailwind's scanner picks it up.
     */
    bg: string;
    text: string;
    border: string;
    /** Omit for a real, working flow doesn't exist yet — renders an inert "Soon" card instead of a dead link. */
    href?: string;
}

interface CustomerQuickActionsProps {
    actions: CustomerQuickAction[];
    /** Grid column classes must be static Tailwind tokens (not template-built), so callers pick the layout that fits their action count. Desktop/`sm:` only when the pill layout is active — mobile is otherwise always a single scrollable row. */
    gridClassName?: string;
    className?: string;
    /** The Dashboard's exception: keep the same tinted card grid at every breakpoint instead of collapsing to a scrollable pill row on mobile. */
    forceCardLayout?: boolean;
}

/**
 * Default (Orders/Repairs): mobile is a single horizontally-scrollable row
 * of solid-color pills (swipe for overflow); `sm:` and up is the tinted
 * card grid from documentation/UI-ref piggyvest-UI-1.jpeg.
 *
 * `forceCardLayout` (Dashboard): the tinted card grid at every breakpoint,
 * no pill/scroll behavior at all.
 */
export default function CustomerQuickActions({
    actions,
    gridClassName = 'grid-cols-2 sm:grid-cols-5',
    className = '',
    forceCardLayout = false,
}: CustomerQuickActionsProps) {
    const containerClasses = forceCardLayout
        ? `grid ${gridClassName} gap-3 ${className}`
        : `flex sm:grid ${gridClassName} overflow-x-auto sm:overflow-visible snap-x snap-mandatory sm:snap-none -mx-4 sm:mx-0 px-4 sm:px-0 pb-1 sm:pb-0 gap-2.5 sm:gap-3 ${className}`;

    return (
        <div className={containerClasses}>
            {actions.map((action) => {
                const cardClasses = forceCardLayout
                    ? `relative flex flex-col gap-3 rounded-customer-card border p-3.5 text-left overflow-hidden ${action.bg} ${action.border}`
                    : `relative flex items-center sm:flex-col sm:items-stretch shrink-0 snap-start gap-2 sm:gap-3 rounded-full sm:rounded-customer-card border-0 sm:border px-4 py-2.5 sm:p-3.5 text-left overflow-hidden whitespace-nowrap sm:whitespace-normal ${action.bg} ${action.border}`;

                const iconBadgeClasses = forceCardLayout
                    ? `w-8 h-8 rounded-customer-pill bg-white/70 flex items-center justify-center shrink-0 ${action.text}`
                    : `w-6 h-6 sm:w-8 sm:h-8 rounded-customer-pill bg-white/25 sm:bg-white/70 flex items-center justify-center shrink-0 ${action.text}`;

                const content = (
                    <>
                        <span className={`${forceCardLayout ? 'block' : 'hidden sm:block'} absolute -right-4 -bottom-4 w-16 h-16 rounded-full bg-white/25`} aria-hidden="true" />
                        <span className="flex items-start justify-between relative">
                            <span className={iconBadgeClasses}>
                                <Icon name={action.icon} />
                            </span>
                            {!action.href && (
                                <span className={`${forceCardLayout ? 'inline-flex' : 'hidden sm:inline-flex'} text-[10px] font-bold uppercase tracking-wide bg-white/70 rounded-customer-pill px-2 py-1 text-customer-text2`}>
                                    Soon
                                </span>
                            )}
                        </span>
                        <span className={`font-bold text-customer-caption relative ${action.text}`}>{action.label}</span>
                    </>
                );

                return action.href ? (
                    <Link key={action.key} href={action.href} className={cardClasses}>
                        {content}
                    </Link>
                ) : (
                    <span key={action.key} aria-disabled="true" className={`${cardClasses} cursor-not-allowed`}>
                        {content}
                    </span>
                );
            })}
        </div>
    );
}
