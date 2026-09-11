import { Head, Link, usePage } from '@inertiajs/react';
import CustomerShell from '../../../Components/Customer/CustomerShell';
import CustomerHeroCard from '../../../Components/Customer/CustomerHeroCard';
import CustomerListItem from '../../../Components/Customer/CustomerListItem';
import CustomerEmptyState from '../../../Components/Customer/CustomerEmptyState';
import Icon from '../../../Components/Icons/Icon';

interface QuickAction {
    key: string;
    label: string;
    icon: string;
}

const QUICK_ACTIONS: QuickAction[] = [
    { key: 'request-repair', label: 'Request a repair', icon: 'tools' },
    { key: 'track-repair', label: 'Track a repair', icon: 'search' },
    { key: 'find-shop', label: 'Find a shop', icon: 'shop' },
    { key: 'refer', label: 'Refer a friend', icon: 'gift' },
];

// Order history is empty until Sales/Orders data flows to the customer
// portal — kept as a typed, empty array (not fabricated rows) so wiring the
// real list later is a data change here, not a UI rebuild.
const recentOrders: Array<{
    id: string;
    icon: string;
    title: string;
    subtitle: string;
    amount: string;
}> = [];

export default function CustomerDashboard() {
    const { auth } = usePage().props as any;
    const customerName: string = auth?.customer?.name ?? 'there';
    const customerPhone: string | undefined = auth?.customer?.phone;
    const customerEmail: string | undefined = auth?.customer?.email;

    return (
        <CustomerShell>
            <Head title="Home" />

            <div className="customer-content-grid py-4 sm:py-0">
                <div>
                    <CustomerHeroCard />

                    <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
                        {QUICK_ACTIONS.map((action) => (
                            <span
                                key={action.key}
                                aria-disabled="true"
                                className="flex flex-col items-center gap-2 bg-white rounded-customer-card shadow-customer-soft p-4 text-center cursor-not-allowed"
                            >
                                <span className="w-9 h-9 rounded-customer-pill bg-customer-blue-soft text-customer-blue flex items-center justify-center">
                                    <Icon name={action.icon} />
                                </span>
                                <span className="text-customer-caption font-semibold text-customer-text2">{action.label}</span>
                                <span className="text-xs uppercase font-semibold text-customer-muted-icon">Soon</span>
                            </span>
                        ))}
                    </div>

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
                        recentOrders.map((order) => (
                            <CustomerListItem
                                key={order.id}
                                icon={order.icon}
                                title={order.title}
                                subtitle={order.subtitle}
                                amount={order.amount}
                            />
                        ))
                    )}
                </div>

                <div>
                    <h2 className="font-bold text-customer-base text-customer-text mb-3">Account</h2>

                    {/* Read-only summary, not a form — editing lives on its own page (Pages/Customer/Profile/Show.tsx) so this card can be elevated without breaking the no-elevated-forms rule (UI/UX §10.1). */}
                    <div className="bg-white rounded-customer-card shadow-customer-soft p-4 mb-6">
                        <div className="flex items-center gap-3 mb-4">
                            <div className="h-10 w-10 rounded-customer-pill bg-customer-blue text-white font-bold flex items-center justify-center shrink-0">
                                {customerName.charAt(0).toUpperCase()}
                            </div>
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
