import { Head, Link, usePage } from '@inertiajs/react';
import CustomerShell from '../../../Components/Customer/CustomerShell';
import CustomerHeroCard from '../../../Components/Customer/CustomerHeroCard';
import CustomerListItem from '../../../Components/Customer/CustomerListItem';
import CustomerEmptyState from '../../../Components/Customer/CustomerEmptyState';
import CustomerAvatar from '../../../Components/Customer/CustomerAvatar';
import CustomerQuickActions, { CustomerQuickAction } from '../../../Components/Customer/CustomerQuickActions';
import Icon from '../../../Components/Icons/Icon';
import { formatNaira } from '../../../lib/money';

type ActiveRepair = {
    device: string;
    status_label: string;
    description: string;
    progress_percent: number;
};

type OrderRow = {
    type: 'checkout' | 'sale';
    id: number;
    status: string;
    total_minor: number;
    invoice_number?: string;
    created_at: string;
};

interface DashboardProps {
    activeRepair: ActiveRepair | null;
    recentOrders: OrderRow[];
}

/** documentation/UI-ref piggyvest-UI-1.jpeg — each card gets its own tint from the existing customer color tokens (no new colors invented), rotating since there's no brand-color-per-feature the way PiggyVest's Savings products have one. Border is the same token at full saturation (vs. the soft/pastel fill), which reads as "the same color, just darker" without inventing a new shade. `track-repair` has a real destination (this dashboard IS the tracker); the rest are flows that don't exist yet. */
const QUICK_ACTIONS: CustomerQuickAction[] = [
    { key: 'buy-phone', label: 'Buy a phone', icon: 'bag-check', bg: 'bg-customer-blue-soft', text: 'text-customer-blue', border: 'border-customer-blue/40' },
    { key: 'request-repair', label: 'Request a repair', icon: 'tools', bg: 'bg-customer-green-soft', text: 'text-customer-green', border: 'border-customer-green/40' },
    { key: 'track-repair', label: 'Track a repair', icon: 'search', bg: 'bg-customer-yellow-soft', text: 'text-customer-yellow-text', border: 'border-customer-yellow-text/40', href: '/customer/repairs' },
    { key: 'find-shop', label: 'Find a shop', icon: 'shop', bg: 'bg-customer-red-soft', text: 'text-customer-red', border: 'border-customer-red/40' },
    { key: 'refer', label: 'Refer a friend', icon: 'gift', bg: 'bg-customer-blue-soft', text: 'text-customer-blue', border: 'border-customer-blue/40' },
];

export default function CustomerDashboard({ activeRepair, recentOrders }: DashboardProps) {
    const { auth } = usePage().props as any;
    const customerName: string = auth?.customer?.name ?? 'there';
    const customerPhone: string | undefined = auth?.customer?.phone;
    const customerEmail: string | undefined = auth?.customer?.email;
    const avatarUrl: string | null = auth?.customer?.avatar_url ?? null;

    return (
        <CustomerShell>
            <Head title="Home" />

            <div className="customer-content-grid py-4 sm:py-0">
                <div>
                    <CustomerHeroCard
                        activeRepair={
                            activeRepair
                                ? {
                                      device: activeRepair.device,
                                      statusLabel: activeRepair.status_label,
                                      description: activeRepair.description,
                                      progressPercent: activeRepair.progress_percent,
                                  }
                                : undefined
                        }
                    />

                    <CustomerQuickActions actions={QUICK_ACTIONS} className="mb-6" forceCardLayout />

                    <div className="flex items-center justify-between mb-3">
                        <h2 className="font-bold text-customer-base text-customer-text">Order history</h2>
                    </div>

                    {recentOrders.length === 0 ? (
                        <CustomerEmptyState
                            icon="bag"
                            description="No orders or repairs yet — once you make a purchase or book a repair, it'll show up here."
                            className="bg-white rounded-customer-card shadow-customer-soft"
                        />
                    ) : (
                        <div className="bg-white rounded-customer-card shadow-customer-soft px-4 overflow-hidden">
                            {recentOrders.map((order) => (
                                <CustomerListItem
                                    key={`${order.type}-${order.id}`}
                                    icon={order.type === 'sale' ? 'receipt' : 'hourglass-split'}
                                    title={order.invoice_number ?? `Checkout #${order.id}`}
                                    subtitle={order.type === 'sale' ? 'Completed' : 'Awaiting payment'}
                                    amount={formatNaira(order.total_minor)}
                                    bare
                                    details={[{ label: 'Date', value: new Date(order.created_at).toLocaleString() }]}
                                    actions={[
                                        <Link
                                            key="view"
                                            href={order.type === 'sale' ? `/customer/orders/sales/${order.id}` : `/customer/orders/checkouts/${order.id}`}
                                            className="text-customer-blue font-semibold text-customer-caption"
                                        >
                                            View details
                                        </Link>,
                                    ]}
                                />
                            ))}
                        </div>
                    )}
                </div>

                <div>
                    <h2 className="font-bold text-customer-base text-customer-text mb-3">Account</h2>

                    {/* Read-only summary, not a form — editing lives on its own page (Pages/Customer/Profile/Show.tsx) so this card can be elevated without breaking the no-elevated-forms rule (UI/UX §10.1). */}
                    <div className="bg-white rounded-customer-card shadow-customer-soft p-4 mb-6">
                        <div className="flex items-center gap-3 mb-4">
                            <CustomerAvatar name={customerName} avatarUrl={avatarUrl} size={40} />
                            <div className="min-w-0">
                                <div className="font-bold text-customer-text truncate">{customerName}</div>
                                {customerPhone && <div className="text-customer-caption text-customer-text2 truncate">{customerPhone}</div>}
                            </div>
                        </div>
                        {customerEmail && (
                            <div className="text-customer-caption text-customer-text2 truncate mb-4">{customerEmail}</div>
                        )}
                        <Link
                            href="/customer/profile"
                            className="inline-flex items-center gap-1.5 text-customer-blue font-semibold text-customer-caption"
                        >
                            Manage profile
                            <Icon name="arrow-right" />
                        </Link>
                    </div>

                    <h2 className="font-bold text-customer-base text-customer-text mb-3">Notifications</h2>
                    <CustomerEmptyState
                        icon="bell-slash"
                        description="No notifications yet."
                        className="bg-white rounded-customer-card shadow-customer-soft"
                    />
                </div>
            </div>
        </CustomerShell>
    );
}
