import React from 'react';
import AdminShell from '../../Components/Admin/AdminShell';
import { Head } from '@inertiajs/react';

export default function Dashboard() {
    return (
        <AdminShell>
            <Head title="Admin Dashboard" />
            <div className="bg-admin-surface rounded-admin-card border border-admin-border p-6 shadow-sm">
                <h1 className="text-admin-xl font-bold mb-4">Admin Dashboard</h1>
                <p className="text-admin-text2">
                    Welcome to the Phase 2 Admin Shell. This page is protected by the staff auth guard, 
                    active-account check, and shop-context resolution.
                </p>
            </div>
        </AdminShell>
    );
}
