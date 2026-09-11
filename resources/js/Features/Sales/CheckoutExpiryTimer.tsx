import { useEffect, useState } from 'react';
import Icon from '../../Components/Icons/Icon';

/**
 * A live countdown reflecting the server-issued checkout expiry
 * (ADD §15/§16) — a display of server truth, not a client-owned timer.
 * `onExpire` fires once when the clock crosses zero so the page can
 * disable the cart and show the expired-checkout banner.
 */
export default function CheckoutExpiryTimer({ expiresAt, onExpire }: { expiresAt: string; onExpire?: () => void }) {
    const [remainingMs, setRemainingMs] = useState(() => new Date(expiresAt).getTime() - Date.now());
    const [hasFiredExpire, setHasFiredExpire] = useState(false);

    useEffect(() => {
        const interval = setInterval(() => {
            const remaining = new Date(expiresAt).getTime() - Date.now();
            setRemainingMs(remaining);

            if (remaining <= 0 && !hasFiredExpire) {
                setHasFiredExpire(true);
                onExpire?.();
            }
        }, 1000);

        return () => clearInterval(interval);
    }, [expiresAt, hasFiredExpire, onExpire]);

    const isExpired = remainingMs <= 0;
    const totalSeconds = Math.max(0, Math.floor(remainingMs / 1000));
    const minutes = Math.floor(totalSeconds / 60);
    const seconds = totalSeconds % 60;
    const isLow = !isExpired && totalSeconds <= 60;

    return (
        <span
            className={`inline-flex items-center gap-1.5 text-xs font-semibold rounded-admin-badge px-2 py-1 ${
                isExpired ? 'bg-admin-red-soft text-admin-red' : isLow ? 'bg-admin-yellow-soft text-admin-yellow-text' : 'bg-admin-hover text-admin-text2'
            }`}
        >
            <Icon name="stopwatch" />
            {isExpired ? 'Expired' : `${minutes}:${seconds.toString().padStart(2, '0')}`}
        </span>
    );
}
