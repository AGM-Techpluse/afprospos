import { useEffect, useRef, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import Icon from '../Icons/Icon';

interface NotificationRow {
    id: number;
    event_type: string;
    category: string;
    payload: { device_make?: string; device_model?: string; selected_remedy?: string; invoice_number?: string };
    read_at: string | null;
    created_at: string;
}

const TITLES: Record<string, string> = {
    RepairCompleted: 'Your repair is complete',
    RepairReadyForCollection: 'Ready for collection',
    WarrantyClaimSubmitted: 'Warranty claim received',
    WarrantyClaimResolved: 'Warranty claim resolved',
    SaleCompleted: 'Thank you for your purchase',
    ReturnRequestResolved: 'Return resolved',
    TradeInCreditApplied: 'Trade-in credit applied',
};

const LINKS: Record<string, string> = {
    RepairCompleted: '/customer/repairs',
    RepairReadyForCollection: '/customer/repairs',
    WarrantyClaimSubmitted: '/customer/warranty/claims',
    WarrantyClaimResolved: '/customer/warranty/claims',
    SaleCompleted: '/customer/orders',
    ReturnRequestResolved: '/customer/warranty/returns',
    TradeInCreditApplied: '/customer/orders',
};

function bodyFor(notification: NotificationRow): string {
    if (notification.event_type === 'RepairCompleted' || notification.event_type === 'RepairReadyForCollection') {
        const device = [notification.payload.device_make, notification.payload.device_model].filter(Boolean).join(' ');
        return device !== '' ? `Your ${device} is ready.` : 'Your device is ready.';
    }

    if (notification.event_type === 'WarrantyClaimResolved' && notification.payload.selected_remedy) {
        return `Remedy: ${notification.payload.selected_remedy}.`;
    }

    if (notification.event_type === 'SaleCompleted') {
        return notification.payload.invoice_number
            ? `Receipt ${notification.payload.invoice_number} is ready to view.`
            : 'Your receipt is ready to view.';
    }

    return 'View details for more information.';
}

/** Bell icon + panel, fed by the `notifications` shared Inertia prop (HandleInertiaRequests) so every Customer page has fresh-enough data without a dedicated fetch. */
export default function NotificationDropdown() {
    const { notifications } = usePage().props as any;
    const data: NotificationRow[] = notifications?.data ?? [];
    const unreadCount: number = notifications?.unread_count ?? 0;
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
        router.post(`/customer/notifications/${id}/read`, {}, { preserveScroll: true, preserveState: true });
    }

    return (
        <div className="relative" ref={containerRef}>
            <button
                type="button"
                onClick={() => setOpen((current) => !current)}
                aria-label="Notifications"
                aria-expanded={open}
                className="relative h-8 w-8 sm:h-10 sm:w-10 rounded-customer-pill bg-white shadow-customer-soft flex items-center justify-center text-customer-text2"
            >
                <Icon name="bell" />
                {unreadCount > 0 && (
                    <span className="absolute -top-0.5 -right-0.5 h-4 min-w-4 px-1 rounded-full bg-customer-red text-white text-[10px] font-bold flex items-center justify-center">
                        {unreadCount > 9 ? '9+' : unreadCount}
                    </span>
                )}
            </button>

            {open && (
                <div
                    role="menu"
                    className="absolute right-0 top-full mt-2 w-80 bg-white rounded-customer-card shadow-customer-strong border border-customer-divider p-4 z-50 max-h-96 overflow-y-auto"
                >
                    <div className="flex items-center justify-between mb-3">
                        <span className="font-semibold text-customer-text text-customer-base">Notifications</span>
                    </div>

                    {data.length === 0 ? (
                        <div className="flex flex-col items-center text-center py-6">
                            <div className="w-10 h-10 rounded-customer-pill bg-customer-page-start flex items-center justify-center text-customer-blue text-lg mb-2">
                                <Icon name="bell-slash" />
                            </div>
                            <p className="text-customer-text2 text-customer-caption">No notifications yet.</p>
                        </div>
                    ) : (
                        <div className="flex flex-col gap-1">
                            {data.map((notification) => (
                                <Link
                                    key={notification.id}
                                    href={LINKS[notification.event_type] ?? '/customer/dashboard'}
                                    onClick={() => notification.read_at === null && markRead(notification.id)}
                                    className={`block rounded-customer-item px-2 py-2 text-left ${notification.read_at === null ? 'bg-customer-blue-soft' : 'hover:bg-gray-50'}`}
                                >
                                    <p className="text-customer-caption font-semibold text-customer-text">
                                        {TITLES[notification.event_type] ?? notification.event_type}
                                    </p>
                                    <p className="text-xs text-customer-text2">{bodyFor(notification)}</p>
                                    <p className="text-[10px] text-customer-text2 mt-1">{new Date(notification.created_at).toLocaleString()}</p>
                                </Link>
                            ))}
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}
