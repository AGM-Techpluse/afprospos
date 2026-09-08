import React from 'react';
import CustomerShell from '../../Components/Customer/CustomerShell';
import { Head } from '@inertiajs/react';

export default function Dashboard() {
    return (
        <CustomerShell>
            <Head title="My Dashboard" />
            
            <div className="bg-white rounded-customer-card shadow-customer-soft p-6 border border-customer-divider mb-6">
                <h1 className="text-customer-2xl font-bold font-customer-display text-customer-text mb-2">Welcome Back!</h1>
                <p className="text-customer-text2">
                    Reaching this page proves the customer auth guard is working end to end. 
                    This is the new Phase 2 Customer Shell layout.
                </p>
            </div>
            
            {/* Empty states for data that isn't ready yet */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="bg-customer-page-start rounded-customer-card p-4 flex flex-col justify-center items-center text-center min-h-[120px]">
                    <h2 className="font-semibold text-customer-blue mb-1">My Repairs</h2>
                    <p className="text-customer-sm text-customer-blue opacity-80">No repairs created yet.</p>
                </div>
                <div className="bg-customer-yellow-soft rounded-customer-card p-4 border border-customer-divider flex flex-col justify-center items-center text-center min-h-[120px]">
                    <h2 className="font-semibold text-customer-yellow-text mb-1">Store Credit</h2>
                    <p className="text-customer-sm text-customer-text2">Not available yet.</p>
                </div>
            </div>
        </CustomerShell>
    );
}
