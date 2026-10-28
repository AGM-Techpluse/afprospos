import React from 'react';
import { usePage } from '@inertiajs/react';
import { AdminToastViewport } from '../Feedback/Toast';
import { ConfirmDialogHost } from '../Feedback/ConfirmDialog';

interface AdminAuthShellProps {
    children: React.ReactNode;
}

export default function AdminAuthShell({ children }: AdminAuthShellProps) {
    const { site_theme_accent } = usePage().props as any;

    return (
        <div
            data-theme="admin"
            className="min-h-screen bg-admin-bg text-admin-text font-admin-base flex items-center justify-center p-4"
            style={{ '--a-blue': site_theme_accent } as React.CSSProperties}
        >
            {children}
            <AdminToastViewport />
            <ConfirmDialogHost />
        </div>
    );
}
