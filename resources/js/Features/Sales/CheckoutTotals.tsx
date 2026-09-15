import { formatNaira } from '../../lib/money';

interface CheckoutTotalsProps {
    subtotalMinor: number;
    discountMinor: number;
    totalMinor: number;
}

/** Money rendered via the monospace stack (UI/UX §4.2). */
export default function CheckoutTotals({ subtotalMinor, discountMinor, totalMinor }: CheckoutTotalsProps) {
    return (
        <dl className="space-y-1.5 text-sm font-mono">
            <div className="flex justify-between">
                <dt className="text-white/60">Subtotal</dt>
                <dd className="text-white">{formatNaira(subtotalMinor)}</dd>
            </div>
            {discountMinor > 0 && (
                <div className="flex justify-between">
                    <dt className="text-white/60">Discount</dt>
                    <dd className="text-white">-{formatNaira(discountMinor)}</dd>
                </div>
            )}
            <div className="flex justify-between text-base font-semibold border-t border-white/15 pt-1.5 mt-1.5">
                <dt className="text-white">Total</dt>
                <dd className="text-admin-green">{formatNaira(totalMinor)}</dd>
            </div>
        </dl>
    );
}
