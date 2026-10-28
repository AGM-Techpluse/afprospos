import { Head, router } from '@inertiajs/react';
import CustomerShell from '../../../../Components/Customer/CustomerShell';
import CustomerListItem from '../../../../Components/Customer/CustomerListItem';
import CustomerEmptyState from '../../../../Components/Customer/CustomerEmptyState';

type WarrantyClaimRow = {
    id: number;
    resolution_state: string;
    selected_remedy: string | null;
    created_at: string;
};

type Paginated<T> = { data: T[]; current_page: number; last_page: number; per_page: number; total: number };

const STATE_LABEL: Record<string, string> = {
    submitted: 'Submitted',
    under_assessment: 'Under assessment',
    eligible: 'Eligible',
    not_eligible: 'Not eligible',
    remedy_selected: 'Remedy selected',
    resolved: 'Resolved',
};

export default function CustomerWarrantyClaimsIndex({ claims }: { claims: Paginated<WarrantyClaimRow> }) {
    return (
        <CustomerShell>
            <Head title="Warranty claims" />

            {/* One wrapping div = one grid item, so this column stays in customer-content-grid's first track. */}
            <div className="customer-content-grid py-4 sm:py-0">
                <div>
                    <div className="flex items-center justify-between mb-1">
                        <h1 className="font-customer-display font-bold text-customer-2xl text-customer-text">Your warranty claims</h1>
                        <button
                            type="button"
                            onClick={() => router.visit('/customer/warranty/claims/create')}
                            className="text-customer-blue font-semibold text-customer-caption"
                        >
                            New claim
                        </button>
                    </div>

                    <button
                        type="button"
                        onClick={() => router.visit('/customer/warranty/returns')}
                        className="text-customer-blue font-semibold text-customer-caption mb-4"
                    >
                        View your returns →
                    </button>

                    {claims.data.length === 0 ? (
                        <CustomerEmptyState
                            icon="shield-exclamation"
                            description="No warranty claims yet — if something you bought or had repaired develops a fault, you can file a claim here."
                            className="bg-white rounded-customer-card shadow-customer-soft"
                        />
                    ) : (
                        <div className="bg-white rounded-customer-card shadow-customer-soft px-4 overflow-hidden">
                            {claims.data.map((claim) => (
                                <CustomerListItem
                                    key={claim.id}
                                    icon="shield-exclamation"
                                    title={`Claim #${claim.id}`}
                                    subtitle={new Date(claim.created_at).toLocaleDateString()}
                                    amount={STATE_LABEL[claim.resolution_state] ?? claim.resolution_state}
                                    bare
                                    details={[
                                        { label: 'Remedy', value: claim.selected_remedy ?? '—' },
                                    ]}
                                    actions={[
                                        <button
                                            key="view"
                                            type="button"
                                            onClick={() => router.visit(`/customer/warranty/claims/${claim.id}`)}
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
