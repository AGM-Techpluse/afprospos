import React from 'react';
import { usePage } from '@inertiajs/react';
import { AdminToastViewport } from '../Feedback/Toast';
import { ConfirmDialogHost } from '../Feedback/ConfirmDialog';
import { useSiteThemeAccent } from '../../hooks/useSiteThemeAccent';

interface AdminAuthShellProps {
    children: React.ReactNode;
}

export default function AdminAuthShell({ children }: AdminAuthShellProps) {
    const { site_theme_accent } = usePage().props as any;

    useSiteThemeAccent(site_theme_accent);

    return (
        <div
            data-theme="admin"
            className="min-h-screen bg-admin-bg text-admin-text font-admin-base flex items-center justify-center p-4"
        >
            {children}
            <AdminToastViewport />
            <ConfirmDialogHost />
        </div>
    );
}
