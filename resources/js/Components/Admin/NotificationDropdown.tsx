import { useEffect, useRef, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import Icon from '../Icons/Icon';

interface NotificationRow {
    id: number;
    event_type: string;
    source_id: number;
    category: string;
    payload: { shop_id?: number; amount_minor?: number };
    read_at: string | null;
    created_at: string;
}

const TITLES: Record<string, string> = {
    CheckoutDiscountApplied: 'Discount awaiting your approval',
    RepairPaymentApprovalRequested: 'Repair payment awaiting your approval',
    RepairDownPaymentApprovalRequested: 'Repair down payment awaiting your approval',
};

const LINKS: Record<string, (notification: NotificationRow) => string> = {
    CheckoutDiscountApplied: (n) => `/admin/sales/checkout?checkout=${n.source_id}`,
    RepairPaymentApprovalRequested: (n) => `/admin/repairs/${n.source_id}`,
    RepairDownPaymentApprovalRequested: (n) => `/admin/repairs/${n.source_id}`,
};

function bodyFor(notification: NotificationRow): string {
    if (notification.event_type === 'CheckoutDiscountApplied') {
        return 'A discount needs your approval before the checkout can be paid.';
    }

    if (notification.event_type === 'RepairPaymentApprovalRequested') {
        return 'A payment recording request needs your approval.';
    }

    if (notification.event_type === 'RepairDownPaymentApprovalRequested') {
        return 'Frontdesk recorded a down payment — it needs your approval.';
    }

    return 'View details for more information.';
}

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

    function markRead(id: number) {
        router.post(`/admin/notifications/${id}/read`, {}, { preserveScroll: true, preserveState: true });
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
                    className="absolute right-0 top-full mt-2 w-80 bg-admin-surface rounded-admin-card shadow-lg border border-admin-border p-4 z-50 max-h-96 overflow-y-auto"
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
                                <Link
                                    key={notification.id}
                                    href={(LINKS[notification.event_type] ?? (() => '/admin/dashboard'))(notification)}
                                    onClick={() => notification.read_at === null && markRead(notification.id)}
                                    className={`block rounded-admin-button px-2 py-2 text-left ${notification.read_at === null ? 'bg-admin-blue/10' : 'hover:bg-admin-hover'}`}
                                >
                                    <p className="text-xs font-semibold text-admin-text">
                                        {TITLES[notification.event_type] ?? notification.event_type}
                                    </p>
                                    <p className="text-xs text-admin-text2">{bodyFor(notification)}</p>
                                    <p className="text-[10px] text-admin-text3 mt-1">{new Date(notification.created_at).toLocaleString()}</p>
                                </Link>
                            ))}
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}
