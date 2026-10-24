import { useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import { useToast } from './useToast';
import { bodyFor, NotificationRow, titleFor } from '../lib/notificationCopy';

function storageKey(staffEmail: string): string {
    return `afprospos.approvalAlerts.lastSeenId.${staffEmail}`;
}

function readLastSeenId(staffEmail: string): number {
    try {
        const raw = window.localStorage.getItem(storageKey(staffEmail));

        return raw !== null ? parseInt(raw, 10) || 0 : 0;
    } catch {
        return 0;
    }
}

function writeLastSeenId(staffEmail: string, id: number): void {
    try {
        window.localStorage.setItem(storageKey(staffEmail), String(id));
    } catch {
        // Private browsing / storage disabled — the alert just won't persist across a fresh page load, not fatal.
    }
}

/**
 * The bell's unread-count badge is easy to miss — a manager approving
 * payments/supplemental quotes from elsewhere in the dashboard has no
 * other cue a request came in. Paired with AdminShell's `usePoll` on
 * `staffNotifications`, this pushes a sticky toast (stays until
 * dismissed, unlike a routine action-confirmation toast) for any
 * newly-arrived unread notification.
 *
 * The "last seen" marker lives in localStorage, not a React ref — a ref
 * resets on every full page navigation (AdminShell remounts), so a
 * notification that arrived while the viewer was on a different page (or
 * before the next poll tick landed) would get silently absorbed into the
 * next mount's baseline and never alert. localStorage survives that.
 * Keyed by email rather than a numeric staff id — the shared `auth.staff`
 * prop (HandleInertiaRequests) only carries name/email, no id.
 */
export function useApprovalAlerts(): void {
    const { staffNotifications, auth } = usePage().props as {
        staffNotifications?: { data: NotificationRow[] } | null;
        auth?: { staff?: { email: string } | null };
    };
    const { toast } = useToast();
    const staffEmail = auth?.staff?.email ?? null;

    useEffect(() => {
        if (staffEmail === null) return;

        const rows = staffNotifications?.data ?? [];
        const highestId = rows.reduce((max, row) => Math.max(max, row.id), 0);
        const lastSeenId = readLastSeenId(staffEmail);
        // Nothing stored yet (a brand-new browser/device for this staff member) — treat the current inbox as already-seen rather than toasting its entire history at once.
        const baseline = lastSeenId === 0 ? highestId : lastSeenId;

        rows
            .filter((row) => row.read_at === null && row.id > baseline)
            .forEach((row) => toast({ kind: 'warning', title: titleFor(row), message: bodyFor(row), sticky: true }));

        if (highestId > 0) {
            writeLastSeenId(staffEmail, Math.max(baseline, highestId));
        }
        // `toast` is a stable context function; re-running only when `staffNotifications`/`staffEmail` changes is what makes each notification alert exactly once.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [staffNotifications, staffEmail]);
}
