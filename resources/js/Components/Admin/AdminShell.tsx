import React, { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import Icon from '../Shared/Icon';

interface AdminShellProps {
    children: React.ReactNode;
}

export default function AdminShell({ children }: AdminShellProps) {
    const { app_name, app_logo } = usePage().props as any;
    const [isMobileDrawerOpen, setIsMobileDrawerOpen] = useState(false);

    return (
        <div data-theme="admin" className="min-h-screen bg-admin-bg text-admin-text font-admin-base flex">
            {/* Desktop Sidebar (Hidden on mobile) */}
            <aside className="hidden sm:flex flex-col w-[220px] bg-admin-surface border-r border-admin-border fixed inset-y-0 z-10">
                <div className="h-[52px] flex items-center px-4 border-b border-admin-border">
                    <img src={app_logo} alt={app_name} className="h-6 w-6 mr-2" />
                    <span className="font-semibold text-admin-base">{app_name}</span>
                </div>
                <nav className="flex-1 py-4 px-2 space-y-1">
                    <Link href="/admin/dashboard" className="flex items-center px-2 py-2 text-admin-text2 hover:bg-admin-hover hover:text-admin-text rounded-admin-button transition-colors">
                        <Icon name="speedometer2" className="mr-3" />
                        <span>Dashboard</span>
                    </Link>
                    {/* Placeholder for other links */}
                    <div className="flex items-center px-2 py-2 text-admin-text3 rounded-admin-button cursor-not-allowed">
                        <Icon name="shop" className="mr-3" />
                        <span>Shops (Soon)</span>
                    </div>
                </nav>
            </aside>

            {/* Mobile Drawer (Hidden on desktop) */}
            {isMobileDrawerOpen && (
                <div className="sm:hidden fixed inset-0 z-40 flex">
                    <div className="fixed inset-0 bg-admin-overlay" onClick={() => setIsMobileDrawerOpen(false)}></div>
                    <aside className="relative flex flex-col w-[230px] max-w-xs h-full bg-admin-surface border-r border-admin-border z-50 transform transition-transform">
                        <div className="h-[52px] flex items-center justify-between px-4 border-b border-admin-border">
                            <div className="flex items-center">
                                <img src={app_logo} alt={app_name} className="h-6 w-6 mr-2" />
                                <span className="font-semibold">{app_name}</span>
                            </div>
                            <button onClick={() => setIsMobileDrawerOpen(false)} className="text-admin-text3 hover:text-admin-text">
                                <Icon name="x-lg" />
                            </button>
                        </div>
                        <nav className="flex-1 py-4 px-2 space-y-1">
                            <Link href="/admin/dashboard" onClick={() => setIsMobileDrawerOpen(false)} className="flex items-center px-2 py-2 text-admin-text hover:bg-admin-hover rounded-admin-button">
                                <Icon name="speedometer2" className="mr-3" />
                                <span>Dashboard</span>
                            </Link>
                        </nav>
                    </aside>
                </div>
            )}

            {/* Main Content Area */}
            <div className="flex-1 sm:ml-[220px] flex flex-col min-h-screen">
                {/* Topbar */}
                <header className="h-[52px] bg-admin-surface border-b border-admin-border flex items-center justify-between px-4 sm:px-6 z-10 sticky top-0">
                    <div className="flex items-center">
                        <button className="sm:hidden mr-4 text-admin-text2 hover:text-admin-text" onClick={() => setIsMobileDrawerOpen(true)}>
                            <Icon name="list" className="text-xl" />
                        </button>
                        <h1 className="text-admin-md font-semibold sm:hidden">Admin</h1>
                    </div>
                    <div className="flex items-center space-x-4 text-admin-text2">
                        {/* Placeholder for topbar actions */}
                        <Icon name="bell" className="cursor-pointer hover:text-admin-text" />
                        <Icon name="person-circle" className="text-xl cursor-pointer hover:text-admin-text" />
                    </div>
                </header>

                {/* Page Content */}
                <main className="flex-1 p-4 sm:pt-[20px] sm:px-[22px] sm:pb-[40px]">
                    {children}
                </main>
            </div>
        </div>
    );
}
