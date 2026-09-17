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

            <div className="customer-content-grid py-4 sm:py-0">
                <div className="flex items-center justify-between mb-4">
                    <h1 className="font-bold text-customer-lg text-customer-text">Your warranty claims</h1>
                    <button
                        type="button"
                        onClick={() => router.visit('/customer/warranty/claims/create')}
                        className="text-customer-blue font-semibold text-customer-caption"
                    >
                        New claim
                    </button>
                </div>

                {claims.data.length === 0 ? (
                    <CustomerEmptyState
                        icon="shield-exclamation"
                        description="No warranty claims yet — if something you bought or had repaired develops a fault, you can file a claim here."
                        className="bg-white rounded-customer-card shadow-customer-soft"
                    />
                ) : (
                    claims.data.map((claim) => (
                        <CustomerListItem
                            key={claim.id}
                            icon="shield-exclamation"
                            title={`Claim #${claim.id}`}
                            subtitle={new Date(claim.created_at).toLocaleDateString()}
                            amount={STATE_LABEL[claim.resolution_state] ?? claim.resolution_state}
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
                    ))
                )}
            </div>
        </CustomerShell>
    );
}
