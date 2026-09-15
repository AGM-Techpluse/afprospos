import { Head, router } from '@inertiajs/react';
import CustomerShell from '../../../Components/Customer/CustomerShell';
import CustomerListItem from '../../../Components/Customer/CustomerListItem';
import CustomerEmptyState from '../../../Components/Customer/CustomerEmptyState';
import { formatNaira } from '../../../lib/money';

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

            <div className="customer-content-grid py-4 sm:py-0">
                <h1 className="font-bold text-customer-lg text-customer-text mb-4">Your repairs</h1>

                {repairs.data.length === 0 ? (
                    <CustomerEmptyState
                        icon="tools"
                        description="No repairs yet — once you drop off a device, it'll show up here."
                        className="bg-white rounded-customer-card shadow-customer-soft"
                    />
                ) : (
                    repairs.data.map((repair) => (
                        <CustomerListItem
                            key={repair.id}
                            icon="tools"
                            title={`${repair.device_make} ${repair.device_model}`}
                            subtitle={repair.repair_status.replace(/_/g, ' ')}
                            amount={formatNaira(repair.labour_charge_minor)}
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
                    ))
                )}
            </div>
        </CustomerShell>
    );
}
