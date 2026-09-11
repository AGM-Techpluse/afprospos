import React, { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import Icon from '../Icons/Icon';
import { CustomerToastViewport } from '../Feedback/Toast';
import NotificationDropdown from './NotificationDropdown';
import CustomerFab from './CustomerFab';

interface CustomerShellProps {
    children: React.ReactNode;
}

interface NavItem {
    key: string;
    label: string;
    icon: string;
    href: string;
    /** Coming-soon items render inert (cursor-not-allowed), not a dead link to a page that 404s. */
    enabled: boolean;
}

const NAV_ITEMS: NavItem[] = [
    { key: 'home', label: 'Home', icon: 'house-door-fill', href: '/customer/dashboard', enabled: true },
    { key: 'repairs', label: 'Repairs', icon: 'tools', href: '/customer/dashboard', enabled: false },
    { key: 'orders', label: 'Orders', icon: 'bag-check', href: '/customer/dashboard', enabled: false },
    { key: 'shops', label: 'Shops', icon: 'shop', href: '/customer/dashboard', enabled: false },
    { key: 'profile', label: 'Profile', icon: 'person', href: '/customer/profile', enabled: true },
];

export default function CustomerShell({ children }: CustomerShellProps) {
    const { app_name, app_logo, auth } = usePage().props as any;
    const currentUrl = usePage().url;
    const [isDrawerOpen, setIsDrawerOpen] = useState(false);
    const customerName: string = auth?.customer?.name ?? 'there';
    const avatarInitial = customerName.charAt(0).toUpperCase();

    return (
        <div data-theme="customer" className="min-h-screen bg-white flex sm:p-4 sm:gap-4">
            {isDrawerOpen && (
                <div
                    className="sm:hidden fixed inset-0 z-40 bg-customer-overlay"
                    onClick={() => setIsDrawerOpen(false)}
                    aria-hidden="true"
                />
            )}

            {/* Sidebar (desktop: persistent rounded floating panel) / Drawer (mobile: off-canvas card) — one element, responsive via CSS per UI/UX §8.5/§8.6. Both panes share the page gradient and read as distinct "floating" surfaces via shadow + rounding, not a color change (§6.1). */}
            <aside
                className={`
                    fixed inset-y-0 left-0 sm:static sm:inset-auto
                    w-customer-drawer sm:w-customer-sidebar
                    bg-customer-page-gradient
                    rounded-r-customer-drawer sm:rounded-customer-drawer
                    sm:shadow-customer-soft
                    z-50 sm:z-auto
                    flex flex-col p-3 sm:p-4
                    transition-transform duration-220
                    ${isDrawerOpen ? 'translate-x-0 shadow-customer-strong' : '-translate-x-full sm:translate-x-0'}
                `}
            >
                <div className="flex items-center gap-2 px-2 pb-4">
                    <img src={app_logo} alt={app_name} className="h-6 w-6" />
                    <span className="font-customer-display font-extrabold text-customer-base text-customer-text">{app_name}</span>
                </div>

                <nav className="flex-1 flex flex-col gap-0.5">
                    {NAV_ITEMS.map((item) => {
                        const isActive = item.enabled && currentUrl === item.href;

                        return item.enabled ? (
                            <Link
                                key={item.key}
                                href={item.href}
                                onClick={() => setIsDrawerOpen(false)}
                                className={`flex items-center gap-2.5 px-3 py-2.5 rounded-customer-item text-customer-md font-semibold ${
                                    isActive ? 'bg-customer-blue-soft text-customer-blue' : 'text-customer-text hover:bg-white/60'
                                }`}
                            >
                                <Icon name={item.icon} className="w-4 text-center" />
                                {item.label}
                            </Link>
                        ) : (
                            <span
                                key={item.key}
                                aria-disabled="true"
                                className="flex items-center gap-2.5 px-3 py-2.5 rounded-customer-item text-customer-md font-semibold text-customer-text2 cursor-not-allowed"
                            >
                                <Icon name={item.icon} className="w-4 text-center" />
                                {item.label}
                            </span>
                        );
                    })}
                </nav>

                <div className="border-t border-customer-divider pt-3">
                    <Link
                        href="/customer/logout"
                        method="post"
                        as="button"
                        className="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-customer-item text-customer-md font-semibold text-customer-text hover:bg-white/60"
                    >
                        <Icon name="box-arrow-right" className="w-4 text-center" />
                        Log out
                    </Link>
                </div>
            </aside>

            {/* Main pane — second grid column: the sidebar's floating-panel partner, gradient background, own rounding/shadow on desktop */}
            <div className="flex-1 min-w-0 flex flex-col relative bg-customer-page-gradient sm:rounded-customer-drawer sm:shadow-customer-soft overflow-hidden">
                <header className="flex items-center gap-3 px-4 sm:px-8 py-4 sm:py-5">
                    <button
                        type="button"
                        onClick={() => setIsDrawerOpen(true)}
                        aria-label="Open menu"
                        className="sm:hidden text-customer-text2"
                    >
                        <Icon name="list" className="text-xl" />
                    </button>
                    <div className="h-10 w-10 rounded-customer-pill bg-customer-blue text-white font-bold flex items-center justify-center shrink-0">
                        {avatarInitial}
                    </div>
                    <div className="font-bold text-customer-lg text-customer-text">
                        Hi, {customerName}
                    </div>
                    <div className="ml-auto">
                        <NotificationDropdown />
                    </div>
                </header>

                <main className="flex-1 overflow-y-auto px-4 sm:px-8 py-5 sm:py-6 pb-customer-content-bottom sm:pb-6">
                    {children}
                </main>

                <CustomerFab />
            </div>

            {/* Mobile taskbar */}
            <nav className="sm:hidden fixed bottom-0 inset-x-0 h-customer-taskbar bg-white border-t border-customer-taskbar-border shadow-customer-strong flex justify-around items-center px-2 z-30">
                <Link
                    href="/customer/dashboard"
                    className={`flex flex-col items-center justify-center w-16 ${currentUrl === '/customer/dashboard' ? 'text-customer-blue' : 'text-customer-text2'}`}
                >
                    <Icon name="house-door-fill" className="text-xl mb-1" />
                    <span className="text-xs font-medium">Home</span>
                </Link>
                <span className="flex flex-col items-center justify-center w-16 text-customer-muted-icon cursor-not-allowed">
                    <Icon name="tools" className="text-xl mb-1" />
                    <span className="text-xs font-medium">Repairs</span>
                </span>
                <span className="flex flex-col items-center justify-center w-16 text-customer-muted-icon cursor-not-allowed">
                    <Icon name="bag-check" className="text-xl mb-1" />
                    <span className="text-xs font-medium">Orders</span>
                </span>
                <Link
                    href="/customer/profile"
                    className={`flex flex-col items-center justify-center w-16 ${currentUrl === '/customer/profile' ? 'text-customer-blue' : 'text-customer-text2'}`}
                >
                    <Icon name="person" className="text-xl mb-1" />
                    <span className="text-xs font-medium">Profile</span>
                </Link>
            </nav>

            <CustomerToastViewport />
        </div>
    );
}
