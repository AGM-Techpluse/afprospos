import { useEffect, useRef, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import Icon from '../Icons/Icon';
import { bodyFor, linkFor, NotificationRow, titleFor } from '../../lib/notificationCopy';

/** Bell icon + panel, fed by the `staffNotifications` shared Inertia prop (HandleInertiaRequests) so every Admin page has fresh-enough data without a dedicated fetch. */
export default function NotificationDropdown() {
    const { staffNotifications } = usePage().props as any;
    const data: NotificationRow[] = staffNotifications?.data ?? [];
    const unreadCount: number = staffNotifications?.unread_count ?? 0;
    const [open, setOpen] = useState(false);
    const containerRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (!open) return;

        const handleClickOutside = (e: MouseEvent) => {
            if (containerRef.current && !containerRef.current.contains(e.target as Node)) {
                setOpen(false);
            }
        };
        const handleEscape = (e: KeyboardEvent) => {
            if (e.key === 'Escape') setOpen(false);
        };

        document.addEventListener('mousedown', handleClickOutside);
        document.addEventListener('keydown', handleEscape);
        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
            document.removeEventListener('keydown', handleEscape);
        };
    }, [open]);

    /**
     * Marking as read and navigating to the notification's target were
     * previously two independent Inertia visits fired from the same click
     * (a `<Link href>` plus a `router.post` in `onClick`) — whichever one
     * resolved last "won" and became the displayed page, so mark-read's
     * `back()` redirect could clobber the real navigation and strand the
     * viewer on whatever page they'd started from instead of the intended
     * target. Sequencing them removes the race: navigate only once
     * mark-read has actually finished.
     */
    function openNotification(notification: NotificationRow) {
        const url = linkFor(notification);

        if (notification.read_at === null) {
            router.post(
                `/admin/notifications/${notification.id}/read`,
                {},
                { preserveScroll: true, preserveState: true, onFinish: () => router.visit(url) },
            );
        } else {
            router.visit(url);
        }
    }

    return (
        <div className="relative" ref={containerRef}>
            <button
                type="button"
                onClick={() => setOpen((current) => !current)}
                aria-label="Notifications"
                aria-expanded={open}
                className="relative cursor-pointer hover:text-admin-text"
            >
                <Icon name="bell" />
                {unreadCount > 0 && (
                    <span className="absolute -top-1.5 -right-1.5 h-4 min-w-4 px-1 rounded-full bg-admin-red text-white text-[10px] font-bold flex items-center justify-center">
                        {unreadCount > 9 ? '9+' : unreadCount}
                    </span>
                )}
            </button>

            {open && (
                <div
                    role="menu"
                    className="absolute right-0 top-full mt-2 w-80 max-w-[calc(100vw-2rem)] bg-admin-surface rounded-admin-card shadow-lg border border-admin-border p-4 z-50 max-h-96 overflow-y-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                >
                    <div className="flex items-center justify-between mb-3">
                        <span className="font-semibold text-admin-text text-sm">Notifications</span>
                    </div>

                    {data.length === 0 ? (
                        <div className="flex flex-col items-center text-center py-6">
                            <Icon name="bell-slash" className="text-admin-blue text-lg mb-2" />
                            <p className="text-admin-text3 text-xs">No notifications yet.</p>
                        </div>
                    ) : (
                        <div className="flex flex-col gap-1">
                            {data.map((notification) => (
                                <button
                                    key={notification.id}
                                    type="button"
                                    onClick={() => openNotification(notification)}
                                    className={`block w-full rounded-admin-button px-2 py-2 text-left cursor-pointer ${notification.read_at === null ? 'bg-admin-blue/10' : 'hover:bg-admin-hover'}`}
                                >
                                    <p className="text-xs font-semibold text-admin-text">{titleFor(notification)}</p>
                                    <p className="text-xs text-admin-text2">{bodyFor(notification)}</p>
                                    <p className="text-[10px] text-admin-text3 mt-1">{new Date(notification.created_at).toLocaleString()}</p>
                                </button>
                            ))}
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}
