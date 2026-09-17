import { Head } from '@inertiajs/react';
import CustomerShell from '../../../../Components/Customer/CustomerShell';

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
    submitted: 'Submitted — waiting on assessment',
    under_assessment: 'Under assessment',
    eligible: 'Eligible — remedy pending',
    not_eligible: 'Not eligible',
    remedy_selected: 'Remedy selected — being processed',
    resolved: 'Resolved',
};

export default function CustomerWarrantyClaimShow({ claim }: CustomerWarrantyClaimShowProps) {
    const origin = claim.originating_repair_job
        ? `${claim.originating_repair_job.device_make} ${claim.originating_repair_job.device_model} (Repair #${claim.originating_repair_job.id})`
        : claim.originating_sale
            ? `Sale #${claim.originating_sale.id}`
            : null;

    return (
        <CustomerShell>
            <Head title={`Warranty claim #${claim.id}`} />

            <div className="customer-content-grid py-4 sm:py-0">
                <h1 className="font-bold text-customer-lg text-customer-text mb-1">Claim #{claim.id}</h1>
                <p className="text-customer-caption text-customer-text2 mb-4">
                    {STATE_LABEL[claim.resolution_state] ?? claim.resolution_state}
                </p>

                <div className="bg-white rounded-customer-card shadow-customer-soft p-4 mb-4">
                    <div className="flex items-center justify-between py-2 border-b border-gray-100">
                        <span className="text-customer-text text-customer-caption">Policy</span>
                        <span className="text-customer-text font-semibold text-customer-caption">
                            {claim.warranty_policy?.name ?? '—'}
                        </span>
                    </div>
                    {origin && (
                        <div className="flex items-center justify-between py-2 border-b border-gray-100">
                            <span className="text-customer-text text-customer-caption">Relates to</span>
                            <span className="text-customer-text font-semibold text-customer-caption">{origin}</span>
                        </div>
                    )}
                    <div className="flex items-center justify-between pt-2">
                        <span className="text-customer-text text-customer-caption">Remedy</span>
                        <span className="text-customer-text font-semibold text-customer-caption capitalize">
                            {claim.selected_remedy ?? 'Not yet determined'}
                        </span>
                    </div>
                </div>
            </div>
        </CustomerShell>
    );
}
