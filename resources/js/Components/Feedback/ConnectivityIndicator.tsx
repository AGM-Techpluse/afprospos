import Icon from '../Icons/Icon';
import useOnlineStatus from '../../hooks/useOnlineStatus';

interface ConnectivityIndicatorProps {
    /** Wired to the real Sync module once it exists (out of scope this phase) — defaults false so nothing is fabricated. */
    syncing?: boolean;
}

/**
 * UI/UX §14A.1 — silent by default (online = no indicator). Small,
 * non-intrusive, theme-agnostic pill for the Admin topbar / Customer shell
 * header. Never escalates to a banner on its own — see AlertBanner + §14A.3
 * for the disabled-control + banner pairing on unsafe-offline actions.
 */
export default function ConnectivityIndicator({ syncing = false }: ConnectivityIndicatorProps) {
    const isOnline = useOnlineStatus();

    if (isOnline && !syncing) return null;

    const label = !isOnline ? 'Offline' : 'Syncing…';
    const icon = !isOnline ? 'wifi-off' : 'arrow-repeat';

    return (
        <span
            className={`
                inline-flex items-center gap-1.5 rounded-admin-badge px-2 py-1 text-xs font-semibold
                ${!isOnline ? 'bg-ui-border text-ui-text-secondary' : 'bg-ui-info-soft text-ui-info'}
            `}
        >
            <Icon name={icon} className={syncing && isOnline ? 'animate-spin' : ''} />
            {label}
        </span>
    );
}
