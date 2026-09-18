import { Head, router } from '@inertiajs/react';
import CustomerShell from '../../../Components/Customer/CustomerShell';
import CustomerListItem from '../../../Components/Customer/CustomerListItem';
import CustomerEmptyState from '../../../Components/Customer/CustomerEmptyState';
import CustomerQuickActions, { CustomerQuickAction } from '../../../Components/Customer/CustomerQuickActions';
import { formatNaira } from '../../../lib/money';

const REPAIRS_QUICK_ACTIONS: CustomerQuickAction[] = [
    { key: 'request-repair', label: 'Request a repair', icon: 'tools', bg: 'bg-customer-green sm:bg-customer-green-soft', text: 'text-white sm:text-customer-green', border: 'border-customer-green/40' },
    { key: 'claim-warranty', label: 'File a warranty claim', icon: 'shield-exclamation', bg: 'bg-customer-blue sm:bg-customer-blue-soft', text: 'text-white sm:text-customer-blue', border: 'border-customer-blue/40', href: '/customer/warranty/claims/create' },
    { key: 'find-shop', label: 'Find a shop', icon: 'shop', bg: 'bg-customer-red sm:bg-customer-red-soft', text: 'text-white sm:text-customer-red', border: 'border-customer-red/40' },
];

type RepairRow = {
    id: number;
    device_make: string;
    device_model: string;
    repair_status: string;
    financial_status: string;
    labour_charge_minor: number;
    estimated_collection_date: string | null;
    created_at: string;
};

type Paginated<T> = { data: T[]; current_page: number; last_page: number; per_page: number; total: number };

/** Read-only repair tracker (Implementation Plan Phase 6 UI list) — mirrors Customer/Orders/Index exactly, no customer-initiated actions. */
export default function CustomerRepairsIndex({ repairs }: { repairs: Paginated<RepairRow> }) {
    return (
        <CustomerShell>
            <Head title="Repairs" />

            {/* One wrapping div = one grid item, so this whole column stays in customer-content-grid's first track — a bare h1 + list as direct grid children get auto-placed across BOTH columns instead of stacking. */}
            <div className="customer-content-grid py-4 sm:py-0">
                <div>
                    <h1 className="font-customer-display font-extrabold text-customer-2xl text-customer-text mb-4">Your repairs</h1>

                    <CustomerQuickActions actions={REPAIRS_QUICK_ACTIONS} gridClassName="grid-cols-2 sm:grid-cols-3" className="mb-6" />

                    {repairs.data.length === 0 ? (
                        <CustomerEmptyState
                            icon="tools"
                            description="No repairs yet — once you drop off a device, it'll show up here."
                            className="bg-white rounded-customer-card shadow-customer-soft"
                        />
                    ) : (
                        <div className="bg-white rounded-customer-card shadow-customer-soft px-4 overflow-hidden">
                            {repairs.data.map((repair) => (
                                <CustomerListItem
                                    key={repair.id}
                                    icon="tools"
                                    title={`${repair.device_make} ${repair.device_model}`}
                                    subtitle={repair.repair_status.replace(/_/g, ' ')}
                                    amount={formatNaira(repair.labour_charge_minor)}
                                    bare
                                    details={[
                                        { label: 'Received', value: new Date(repair.created_at).toLocaleDateString() },
                                        {
                                            label: 'Estimated collection',
                                            value: repair.estimated_collection_date ? new Date(repair.estimated_collection_date).toLocaleDateString() : '—',
                                        },
                                    ]}
                                    actions={[
                                        <button
                                            key="view"
                                            type="button"
                                            onClick={() => router.visit(`/customer/repairs/${repair.id}`)}
                                            className="text-customer-blue font-semibold text-customer-caption"
                                        >
                                            View details
                                        </button>,
                                    ]}
                                />
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </CustomerShell>
    );
}
