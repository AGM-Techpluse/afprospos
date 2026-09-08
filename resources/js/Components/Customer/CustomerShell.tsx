import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import Icon from '../Shared/Icon';

interface CustomerShellProps {
    children: React.ReactNode;
}

export default function CustomerShell({ children }: CustomerShellProps) {
    const { app_name, app_logo } = usePage().props as any;

    return (
        <div data-theme="customer" className="min-h-screen bg-[var(--c-gradient-page)] text-customer-text font-customer-ui flex flex-col relative pb-[70px] sm:pb-0">
            {/* Desktop Topbar / Header */}
            <header className="hidden sm:flex h-[72px] items-center justify-between px-8 mx-auto w-full max-w-7xl">
                <Link href="/customer/dashboard" className="flex items-center">
                    <img src={app_logo} alt={app_name} className="h-8 w-8 mr-3" />
                    <span className="font-customer-display font-bold text-customer-xl">{app_name}</span>
                </Link>
                <nav className="flex items-center space-x-6">
                    <Link href="/customer/dashboard" className="text-customer-text font-medium hover:text-customer-blue transition-colors">
                        Home
                    </Link>
                    <span className="text-customer-text2 font-medium cursor-not-allowed">Repairs</span>
                    <span className="text-customer-text2 font-medium cursor-not-allowed">Profile</span>
                </nav>
                <div className="flex items-center">
                    <div className="h-10 w-10 rounded-customer-pill bg-white shadow-customer-soft flex items-center justify-center cursor-pointer">
                        <Icon name="person" className="text-customer-blue text-lg" />
                    </div>
                </div>
            </header>

            {/* Mobile Header */}
            <header className="sm:hidden flex h-[60px] items-center justify-between px-4 bg-transparent">
                <div className="flex items-center">
                    <img src={app_logo} alt={app_name} className="h-7 w-7 mr-2" />
                    <span className="font-customer-display font-bold text-customer-lg">{app_name}</span>
                </div>
                <div className="h-8 w-8 rounded-customer-pill bg-white shadow-customer-soft flex items-center justify-center">
                    <Icon name="bell" className="text-customer-text2" />
                </div>
            </header>

            {/* Main Content Area */}
            <main className="flex-1 w-full max-w-7xl mx-auto px-4 sm:px-8 py-6">
                {children}
            </main>

            {/* Mobile Bottom Taskbar */}
            <nav className="sm:hidden fixed bottom-0 inset-x-0 h-[70px] bg-white border-t border-customer-taskbar-border shadow-customer-strong flex justify-around items-center px-2 z-50 rounded-t-customer-shell">
                <Link href="/customer/dashboard" className="flex flex-col items-center justify-center w-16 text-customer-blue">
                    <Icon name="house-door-fill" className="text-xl mb-1" />
                    <span className="text-xs font-medium">Home</span>
                </Link>
                <div className="flex flex-col items-center justify-center w-16 text-customer-muted-icon cursor-not-allowed">
                    <Icon name="tools" className="text-xl mb-1" />
                    <span className="text-xs font-medium">Repairs</span>
                </div>
                <div className="flex flex-col items-center justify-center w-16 text-customer-muted-icon cursor-not-allowed">
                    <Icon name="person" className="text-xl mb-1" />
                    <span className="text-xs font-medium">Profile</span>
                </div>
            </nav>
        </div>
    );
}
