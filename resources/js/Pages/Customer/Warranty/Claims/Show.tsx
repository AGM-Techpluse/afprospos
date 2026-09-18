import { Head, Link } from '@inertiajs/react';
import CustomerShell from '../../../../Components/Customer/CustomerShell';
import CustomerDetailCard from '../../../../Components/Customer/CustomerDetailCard';

interface WarrantyClaimDetail {
    id: number;
    warranty_policy: { name: string; coverage_duration_days: number } | null;
    originating_sale: { id: number } | null;
    originating_repair_job: { id: number; device_make: string; device_model: string } | null;
    resolution_state: string;
    selected_remedy: string | null;
    created_at: string;
}

interface CustomerWarrantyClaimShowProps {
    claim: WarrantyClaimDetail;
}

const STATE_LABEL: Record<string, string> = {
    submitted: 'Submitted',
    under_assessment: 'Under assessment',
    eligible: 'Eligible',
    not_eligible: 'Not eligible',
    remedy_selected: 'Remedy selected',
    resolved: 'Resolved',
};

const STATE_TONE: Record<string, 'success' | 'warning' | 'danger' | 'info'> = {
    submitted: 'warning',
    under_assessment: 'warning',
    eligible: 'info',
    not_eligible: 'danger',
    remedy_selected: 'info',
    resolved: 'success',
};

export default function CustomerWarrantyClaimShow({ claim }: CustomerWarrantyClaimShowProps) {
    const origin = claim.originating_repair_job
        ? `${claim.originating_repair_job.device_make} ${claim.originating_repair_job.device_model} (Repair #${claim.originating_repair_job.id})`
        : claim.originating_sale
            ? `Sale #${claim.originating_sale.id}`
            : null;

    const originHref = claim.originating_repair_job
        ? `/customer/repairs/${claim.originating_repair_job.id}`
        : claim.originating_sale
            ? `/customer/orders/sales/${claim.originating_sale.id}`
            : null;

    return (
        <CustomerShell>
            <Head title={`Warranty claim #${claim.id}`} />

            {/* One wrapping div = one grid item, so this column stays in customer-content-grid's first track. */}
            <div className="customer-content-grid py-4 sm:py-0">
                <div>
                    <h1 className="font-customer-display font-extrabold text-customer-2xl text-customer-text mb-4">Claim #{claim.id}</h1>

                    <CustomerDetailCard
                        icon="shield-exclamation"
                        title={`Claim #${claim.id}`}
                        subtitle={new Date(claim.created_at).toLocaleDateString()}
                        badge={{ label: STATE_LABEL[claim.resolution_state] ?? claim.resolution_state, tone: STATE_TONE[claim.resolution_state] ?? 'info' }}
                        details={[
                            { label: 'Policy', value: claim.warranty_policy?.name ?? '—' },
                            { label: 'Relates to', value: origin ?? '—' },
                            { label: 'Remedy', value: claim.selected_remedy ?? 'Not yet determined' },
                        ]}
                        actions={
                            originHref && (
                                <Link href={originHref} className="text-customer-blue font-semibold text-customer-caption">
                                    View original order
                                </Link>
                            )
                        }
                    />
                </div>
            </div>
        </CustomerShell>
    );
}
