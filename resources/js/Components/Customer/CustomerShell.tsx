import React, { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import Icon from '../Icons/Icon';
import { CustomerToastViewport } from '../Feedback/Toast';
import { ConfirmDialogHost } from '../Feedback/ConfirmDialog';
import NotificationDropdown from './NotificationDropdown';
import CustomerFab from './CustomerFab';
import CustomerAvatar from './CustomerAvatar';
import { useFlashToast } from '../../hooks/useFlashToast';

interface CustomerShellProps {
    children: React.ReactNode;
}

interface NavItem {
    key: string;
    label: string;
    icon: string;
    iconBg: string;
    href: string;
    /** Coming-soon items render inert (cursor-not-allowed), not a dead link to a page that 404s. */
    enabled: boolean;
}

/** `shops` is the one item whose `enabled` isn't a fixed "coming soon" — it tracks the storefront feature flag (STOREFRONT_ENABLED), so switching the storefront off also makes its own nav entry render inert instead of linking to a route that now 404s. */
function buildNavItems(storefrontEnabled: boolean): NavItem[] {
    return [
        { key: 'home', label: 'Home', icon: 'house-door-fill', iconBg: 'bg-customer-blue', href: '/customer/dashboard', enabled: true },
        { key: 'repairs', label: 'Repairs', icon: 'tools', iconBg: 'bg-customer-green', href: '/customer/repairs', enabled: true },
        { key: 'orders', label: 'Orders', icon: 'bag-check', iconBg: 'bg-customer-yellow-text', href: '/customer/orders', enabled: true },
        { key: 'warranty', label: 'Warranty', icon: 'shield-exclamation', iconBg: 'bg-customer-red', href: '/customer/warranty/claims', enabled: true },
        { key: 'shops', label: 'Shop', icon: 'shop', iconBg: 'bg-customer-card-lime-text', href: '/shop', enabled: storefrontEnabled },
        { key: 'profile', label: 'Profile', icon: 'person', iconBg: 'bg-gray-500', href: '/customer/profile', enabled: true },
    ];
}

export default function CustomerShell({ children }: CustomerShellProps) {
    const { app_name, app_logo, auth, storefrontEnabled } = usePage().props as any;
    const currentUrl = usePage().url;
    const [isDrawerOpen, setIsDrawerOpen] = useState(false);
    const customerName: string = auth?.customer?.name ?? 'there';
    const avatarUrl: string | null = auth?.customer?.avatar_url ?? null;
    const navItems = buildNavItems(Boolean(storefrontEnabled));

    useFlashToast();

    return (
        <div data-theme="customer" className="min-h-screen bg-white flex">
            {isDrawerOpen && (
                <div
                    className="sm:hidden fixed inset-0 z-40 bg-customer-overlay"
                    onClick={() => setIsDrawerOpen(false)}
                    aria-hidden="true"
                />
            )}

            {/* Flat white sidebar (documentation/UI-ref travel-booking-sidebar reference) — a plain nav list, not a floating gradient panel; the gradient stays on the main content only. Rounded on the right edge like the reference's drawer card on mobile; flush/square on desktop, matching the reference's flat desktop layout. print:hidden so printing a receipt/invoice doesn't include app chrome. */}
            <aside
                className={`
                    print:hidden
                    fixed inset-y-0 left-0 sm:static sm:inset-auto
                    w-customer-drawer sm:w-customer-sidebar
                    bg-white border-r border-gray-100
                    rounded-r-customer-drawer sm:rounded-none
                    z-50 sm:z-auto
                    flex flex-col p-4 sm:p-5
                    transition-transform duration-220
                    ${isDrawerOpen ? 'translate-x-0 shadow-customer-strong' : '-translate-x-full sm:translate-x-0'}
                `}
            >
                <div className="flex items-center gap-2 px-2 pt-4 pb-6">
                    <img src={app_logo} alt={app_name} className="h-10 w-auto max-w-[160px] object-contain" />
                </div>

                <div className="border-b border-gray-100 -mx-4 sm:-mx-5" />

                <nav className="flex-1 flex flex-col gap-2 justify-start pt-4 overflow-y-auto">
                    {navItems.map((item) => {
                        const isActive = item.enabled && currentUrl === item.href;

                        return item.enabled ? (
                            <Link
                                key={item.key}
                                href={item.href}
                                onClick={() => setIsDrawerOpen(false)}
                                className={`flex items-center gap-3 px-3 py-3 rounded-customer-item text-customer-lg font-bold transition-colors ${
                                    isActive ? 'bg-customer-blue-soft text-customer-blue' : 'text-customer-text2 hover:bg-gray-50'
                                }`}
                            >
                                <div className={`flex items-center justify-center w-8 h-8 rounded-lg text-white shrink-0 ${isActive ? 'bg-customer-blue' : item.iconBg}`}>
                                    <Icon name={item.icon} className="text-base" />
                                </div>
                                {item.label}
                            </Link>
                        ) : (
                            <span
                                key={item.key}
                                aria-disabled="true"
                                className="flex items-center gap-3 px-3 py-3 rounded-customer-item text-customer-lg font-bold text-customer-text2/50 cursor-not-allowed"
                            >
                                <div className={`flex items-center justify-center w-8 h-8 rounded-lg text-white shrink-0 ${item.iconBg} opacity-50`}>
                                    <Icon name={item.icon} className="text-base" />
                                </div>
                                {item.label}
                                <span className="ml-auto text-[10px] font-bold uppercase tracking-wide text-customer-text2/60">Soon</span>
                            </span>
                        );
                    })}
                </nav>

                <div className="border-t border-gray-100 pt-3">
                    <Link
                        href="/customer/logout"
                        method="post"
                        as="button"
                        className="w-full flex items-center gap-3 px-3 py-3 rounded-customer-item text-customer-lg font-bold text-customer-red hover:bg-customer-red-soft"
                    >
                        <Icon name="box-arrow-right" className="text-xl shrink-0" />
                        Log out
                    </Link>
                </div>
            </aside>

            {/* Main pane — flush against the sidebar, keeps the blue gradient background. */}
            <div className="flex-1 min-w-0 flex flex-col relative bg-customer-page-gradient overflow-hidden">
                <header className="print:hidden flex items-center gap-3 px-4 sm:px-8 py-4 sm:py-5">
                    <button
                        type="button"
                        onClick={() => setIsDrawerOpen(true)}
                        aria-label="Open menu"
                        className="sm:hidden text-customer-text2"
                    >
                        <Icon name="list" className="text-xl" />
                    </button>
                    <CustomerAvatar name={customerName} avatarUrl={avatarUrl} size={40} />
                    <div className="font-bold text-customer-lg text-customer-text">
                        Hi, {customerName}
                    </div>
                    <div className="ml-auto">
                        <NotificationDropdown />
                    </div>
                </header>

                <main className="flex-1 overflow-y-auto px-4 sm:px-8 pt-10 sm:pt-14 pb-customer-content-bottom sm:pb-6">
                    {children}
                </main>

                <CustomerFab />
            </div>

            <CustomerToastViewport />
            <ConfirmDialogHost />
        </div>
    );
}
