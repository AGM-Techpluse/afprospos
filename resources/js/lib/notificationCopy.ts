export interface NotificationRow {
    id: number;
    event_type: string;
    source_id: number;
    category: string;
    payload: { shop_id?: number; shop_name?: string; amount_minor?: number };
    read_at: string | null;
    created_at: string;
}

/** Shared by NotificationDropdown.tsx (the inbox panel) and useApprovalAlerts.ts (the live popup) so both read the same copy. */
const TITLES: Record<string, string> = {
    CheckoutDiscountApplied: 'Checkout adjustment awaiting your approval',
    RepairPaymentApprovalRequested: 'Repair payment awaiting your approval',
    RepairDownPaymentApprovalRequested: 'Repair down payment awaiting your approval',
    SupplementalQuoteRequested: 'Supplemental quote awaiting your approval',
};

const BODIES: Record<string, string> = {
    CheckoutDiscountApplied: 'A discount or trade-in credit needs your approval before the checkout can be paid.',
    RepairPaymentApprovalRequested: 'A payment recording request needs your approval.',
    RepairDownPaymentApprovalRequested: 'Frontdesk recorded a down payment — it needs your approval.',
    SupplementalQuoteRequested: "A technician flagged cost beyond the original quote — it needs your approval before it's added to the balance.",
};

const LINKS: Record<string, (notification: NotificationRow) => string> = {
    CheckoutDiscountApplied: (n) => `/admin/sales/checkout?checkout=${n.source_id}`,
    RepairPaymentApprovalRequested: (n) => `/admin/repairs/${n.source_id}`,
    RepairDownPaymentApprovalRequested: (n) => `/admin/repairs/${n.source_id}`,
    SupplementalQuoteRequested: (n) => `/admin/repairs/${n.source_id}`,
};

export function titleFor(notification: NotificationRow): string {
    return TITLES[notification.event_type] ?? notification.event_type;
}

export function bodyFor(notification: NotificationRow): string {
    const base = BODIES[notification.event_type] ?? 'View details for more information.';

    return notification.payload.shop_name ? `${base} (${notification.payload.shop_name})` : base;
}

export function linkFor(notification: NotificationRow): string {
    return (LINKS[notification.event_type] ?? (() => '/admin/dashboard'))(notification);
}
