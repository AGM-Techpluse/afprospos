import React from 'react';

interface AdminAuthShellProps {
    children: React.ReactNode;
}

export default function AdminAuthShell({ children }: AdminAuthShellProps) {
    return (
        <div data-theme="admin" className="min-h-screen bg-admin-bg text-admin-text font-admin-base flex items-center justify-center p-4">
            {children}
        </div>
    );
}
