import React, { useEffect, useMemo, useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import Icon from '../Icons/Icon';
import AdminAccountMenu from './AdminAccountMenu';
import { AdminToastViewport } from '../Feedback/Toast';

interface AdminShellProps {
    children: React.ReactNode;
}

interface NavItem {
    key: string;
    label: string;
    icon: string;
    href?: string;
    /** Prefix used to decide "is this item active", when it differs from href (e.g. Inventory covers several sub-pages under one link). Defaults to href. */
    activeMatch?: string;
    soon?: boolean;
}

interface NavGroup {
    name: string;
    items: NavItem[];
}

/**
 * Mirrors documentation/AfProsPos_Design_Preview (2).html's admin sidebar
 * (lines 407-427) and UI/UX §8.1 exactly. "Roles" isn't a separate item in
 * the prototype (it merges into one "Staff & roles" entry) — we already
 * built Staff and Roles as two real, separate pages, so they stay split.
 */
const NAV_GROUPS: NavGroup[] = [
    {
        name: 'Operations',
        items: [
            { key: 'dashboard', label: 'Dashboard', icon: 'grid-1x2-fill', href: '/admin/dashboard' },
            { key: 'repairs', label: 'Repairs', icon: 'tools', soon: true },
            { key: 'sales', label: 'Sales / POS', icon: 'receipt', soon: true },
            { key: 'inventory', label: 'Inventory', icon: 'box-seam', href: '/admin/inventory/products', activeMatch: '/admin/inventory' },
            { key: 'payments', label: 'Payments', icon: 'credit-card-2-front', soon: true },
        ],
    },
    {
        name: 'Business',
        items: [
            { key: 'shops', label: 'Multi-shop', icon: 'shop', href: '/admin/shops' },
            { key: 'reports', label: 'Reports', icon: 'bar-chart-line', soon: true },
            { key: 'staff', label: 'Staff', icon: 'people', href: '/admin/staff' },
            { key: 'roles', label: 'Roles', icon: 'person-badge', href: '/admin/staff/roles' },
        ],
    },
];

function useActiveKey(url: string): string | undefined {
    return useMemo(() => {
        return NAV_GROUPS.flatMap((group) => group.items)
            .filter((item) => Boolean(item.href))
            .map((item) => ({ key: item.key, match: item.activeMatch ?? item.href! }))
            .filter(({ match }) => url === match || url.startsWith(`${match}/`))
            .sort((a, b) => b.match.length - a.match.length)[0]?.key;
    }, [url]);
}

function readStoredCollapsed(): boolean {
    try {
        return localStorage.getItem('admin-sidebar-collapsed') === '1';
    } catch {
        return false;
    }
}

function writeStoredCollapsed(collapsed: boolean): void {
    try {
        localStorage.setItem('admin-sidebar-collapsed', collapsed ? '1' : '0');
    } catch {
        // Private browsing / storage disabled — collapse preference just won't persist.
    }
}

function NavLink({
    item,
    active,
    collapsed,
    onNavigate,
}: {
    item: NavItem;
    active: boolean;
    collapsed: boolean;
    onNavigate?: () => void;
}) {
    if (item.soon || !item.href) {
        return (
            <span
                aria-disabled="true"
                title={collapsed ? `${item.label} (soon)` : undefined}
                className="flex items-center gap-admin-nav-gap px-admin-nav-x py-admin-nav-y rounded-admin-button text-admin-text3 cursor-not-allowed mb-px"
            >
                <i className={`bi bi-${item.icon} w-admin-nav-icon inline-flex items-center justify-center shrink-0`} />
                {!collapsed && (
                    <>
                        <span className="flex-1 text-admin-nav truncate">{item.label}</span>
                        <span className="text-[10px] uppercase font-semibold shrink-0">Soon</span>
                    </>
                )}
            </span>
        );
    }

    return (
        <Link
            href={item.href}
            onClick={onNavigate}
            title={collapsed ? item.label : undefined}
            className={`flex items-center gap-admin-nav-gap px-admin-nav-x py-admin-nav-y rounded-admin-button text-admin-nav mb-px transition-colors ${
                active
                    ? 'bg-admin-blue-soft text-admin-blue font-semibold'
                    : 'text-admin-text2 font-medium hover:bg-admin-hover hover:text-admin-text'
            }`}
        >
            <i className={`bi bi-${item.icon} w-admin-nav-icon inline-flex items-center justify-center shrink-0`} />
            {!collapsed && <span className="truncate">{item.label}</span>}
        </Link>
    );
}

function NavGroups({ collapsed, activeKey, onNavigate }: { collapsed: boolean; activeKey?: string; onNavigate?: () => void }) {
    return (
        <>
            {NAV_GROUPS.map((group) => (
                <div key={group.name}>
                    {!collapsed && (
                        <div className="text-[10.5px] font-semibold text-admin-text3 tracking-wide uppercase mt-3.5 mx-2 mb-1.5 first:mt-0">
                            {group.name}
                        </div>
                    )}
                    {group.items.map((item) => (
                        <NavLink
                            key={item.key}
                            item={item}
                            active={item.key === activeKey}
                            collapsed={collapsed}
                            onNavigate={onNavigate}
                        />
                    ))}
                </div>
            ))}
        </>
    );
}

export default function AdminShell({ children }: AdminShellProps) {
    const { url } = usePage();
    const { app_name, app_logo, auth } = usePage().props as any;
    const [isMobileDrawerOpen, setIsMobileDrawerOpen] = useState(false);
    const [collapsed, setCollapsed] = useState(readStoredCollapsed);
    const activeKey = useActiveKey(url);

    useEffect(() => {
        writeStoredCollapsed(collapsed);
    }, [collapsed]);

    const staffName: string = auth?.staff?.name ?? 'Staff';
    const staffEmail: string = auth?.staff?.email ?? '';

    return (
        <div data-theme="admin" className="min-h-screen bg-admin-bg text-admin-text font-admin-base flex">
            {/* Desktop Sidebar (Hidden on mobile) */}
            <aside
                className={`hidden sm:flex flex-col ${collapsed ? 'w-admin-sidebar-collapsed' : 'w-admin-sidebar'} bg-admin-surface border-r border-admin-border fixed inset-y-0 z-10 transition-[width] duration-200`}
            >
                <div className="h-admin-topbar flex items-center px-4 border-b border-admin-border shrink-0">
                    <img src={app_logo} alt={app_name} className="h-6 w-6 mr-2 shrink-0" />
                    {!collapsed && <span className="font-semibold text-admin-base truncate">{app_name}</span>}
                </div>

                <nav className="flex-1 py-4 px-2 overflow-y-auto overflow-x-hidden">
                    <NavGroups collapsed={collapsed} activeKey={activeKey} />
                </nav>

                <div className="mt-auto pt-2 px-2 pb-3 border-t border-admin-border">
                    <button
                        type="button"
                        onClick={() => setCollapsed((current) => !current)}
                        className="flex items-center gap-admin-nav-gap w-full bg-transparent border-0 text-admin-text2 text-xs font-medium px-admin-nav-x py-admin-nav-y rounded-admin-button hover:bg-admin-hover"
                    >
                        <i className={`bi bi-chevron-bar-left w-admin-nav-icon inline-flex items-center justify-center shrink-0 transition-transform duration-200 ${collapsed ? 'rotate-180' : ''}`} />
                        {!collapsed && <span>Collapse sidebar</span>}
                    </button>
                </div>
            </aside>

            {/* Mobile Drawer (Hidden on desktop) */}
            {isMobileDrawerOpen && (
                <div className="sm:hidden fixed inset-0 z-40 flex">
                    <div className="fixed inset-0 bg-admin-overlay" onClick={() => setIsMobileDrawerOpen(false)}></div>
                    <aside className="relative flex flex-col w-admin-drawer max-w-xs h-full bg-admin-surface border-r border-admin-border z-50 transform transition-transform">
                        <div className="h-admin-topbar flex items-center justify-between px-4 border-b border-admin-border shrink-0">
                            <div className="flex items-center">
                                <img src={app_logo} alt={app_name} className="h-6 w-6 mr-2" />
                                <span className="font-semibold">{app_name}</span>
                            </div>
                            <button onClick={() => setIsMobileDrawerOpen(false)} className="text-admin-text3 hover:text-admin-text">
                                <Icon name="x-lg" />
                            </button>
                        </div>
                        <nav className="flex-1 py-4 px-2 overflow-y-auto">
                            <NavGroups collapsed={false} activeKey={activeKey} onNavigate={() => setIsMobileDrawerOpen(false)} />
                        </nav>
                    </aside>
                </div>
            )}

            {/* Main Content Area */}
            <div className={`flex-1 flex flex-col min-h-screen transition-[margin] duration-200 ${collapsed ? 'sm:ml-admin-sidebar-collapsed' : 'sm:ml-admin-sidebar'}`}>
                {/* Topbar */}
                <header className="h-admin-topbar bg-admin-surface border-b border-admin-border flex items-center justify-between px-4 sm:px-6 z-10 sticky top-0">
                    <div className="flex items-center">
                        <button className="sm:hidden mr-4 text-admin-text2 hover:text-admin-text" onClick={() => setIsMobileDrawerOpen(true)}>
                            <Icon name="list" className="text-xl" />
                        </button>
                        <h1 className="text-admin-md font-semibold sm:hidden">Admin</h1>
                    </div>
                    <div className="flex items-center space-x-4 text-admin-text2">
                        <Icon name="bell" className="cursor-pointer hover:text-admin-text" />
                        <AdminAccountMenu name={staffName} email={staffEmail} />
                    </div>
                </header>

                {/* Page Content */}
                <main className="flex-1 p-4 sm:pt-5 sm:px-admin-content-x sm:pb-10">
                    {children}
                </main>
            </div>

            <AdminToastViewport />
        </div>
    );
}
