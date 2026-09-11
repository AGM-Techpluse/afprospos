import React from 'react';
import { Head, usePage } from '@inertiajs/react';
import { CustomerToastViewport } from '../Feedback/Toast';

interface CustomerAuthShellProps {
    children: React.ReactNode;
}

export default function CustomerAuthShell({ children }: CustomerAuthShellProps) {
    const { app_name, app_logo } = usePage().props as any;

    return (
        <div data-theme="customer" className="min-h-screen bg-white text-customer-text font-customer-ui flex flex-col relative">
            {/* Minimal Header just showing brand, no navigation */}
            <header className="flex h-customer-header-desktop items-center px-4 sm:px-8 mx-auto w-full max-w-7xl">
                <div className="flex items-center mt-4">
                    <img src={app_logo} alt={app_name} className="h-8 w-8 mr-3" />
                </div>
            </header>

            {/* Main Content Area */}
            <main className="flex-1 w-full max-w-7xl mx-auto px-4 sm:px-8 py-6 flex flex-col pt-[10vh]">
                {children}
            </main>

            <CustomerToastViewport />
        </div>
    );
}
