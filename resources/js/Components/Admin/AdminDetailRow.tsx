import { ReactNode } from 'react';

interface AdminDetailRowProps {
    label: string;
    value: ReactNode;
}

/**
 * A single key-value row for detail/info cards (UI/UX §6.1).
 *
 * Layout: [fixed 130px label] | [vertical divider] | [value, left-aligned]
 *
 * - Label column is capped at 130px. If the label text is wider than the
 *   column it scrolls as a CSS marquee on hover so the full text is readable
 *   without wrapping or clipping.
 * - A 1px vertical divider separates the label from the value.
 * - Value is left-aligned and grows to fill the remaining space.
 * - Rows are separated by a horizontal bottom border (last row excluded).
 */
export default function AdminDetailRow({ label, value }: AdminDetailRowProps) {
    return (
        <div className="flex items-center py-2.5 border-b border-admin-border last:border-b-0">
            {/* Label column — fixed 130px, marquee on hover when text overflows */}
            <div className="shrink-0 w-[130px] overflow-hidden">
                <span
                    className="detail-label-marquee block text-sm text-admin-text2 whitespace-nowrap truncate"
                    title={label}
                >
                    {label}
                </span>
            </div>

            {/* Vertical divider */}
            <div className="shrink-0 w-px self-stretch bg-admin-border mx-3" aria-hidden="true" />

            {/* Value — left-aligned, fills remaining space */}
            <div className="flex-1 min-w-0 text-sm font-medium text-admin-text">
                {value}
            </div>
        </div>
    );
}
