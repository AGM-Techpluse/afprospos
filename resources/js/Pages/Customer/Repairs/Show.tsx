import { Head } from '@inertiajs/react';
import CustomerShell from '../../../Components/Customer/CustomerShell';
import { formatNaira } from '../../../lib/money';

type Diagnosis = {
    component: string;
    condition: string;
    outcome: string | null;
};

type RepairDetail = {
    id: number;
    device_make: string;
    device_model: string;
    repair_status: string;
    financial_status: string;
    labour_charge_minor: number;
    down_payment_required_minor: number | null;
    estimated_collection_date: string | null;
    created_at: string;
    diagnoses: Diagnosis[];
};

interface CustomerRepairShowProps {
    repair: RepairDetail;
}

/** Read-only repair tracker (Implementation Plan Phase 6 UI list) — mirrors Customer/Orders/Show exactly, no customer-initiated actions. */
export default function CustomerRepairShow({ repair }: CustomerRepairShowProps) {
    return (
        <CustomerShell>
            <Head title={`${repair.device_make} ${repair.device_model}`} />

            <div className="customer-content-grid py-4 sm:py-0">
                <h1 className="font-bold text-customer-lg text-customer-text mb-1">
                    {repair.device_make} {repair.device_model}
                </h1>
                <p className="text-customer-caption text-customer-text2 mb-4 capitalize">{repair.repair_status.replace(/_/g, ' ')}</p>

                <div className="bg-white rounded-customer-card shadow-customer-soft p-4 mb-4">
                    <div className="flex items-center justify-between py-2 border-b border-gray-100">
                        <span className="text-customer-text text-customer-caption">Labour charge</span>
                        <span className="text-customer-text font-semibold text-customer-caption">{formatNaira(repair.labour_charge_minor)}</span>
                    </div>
                    {repair.down_payment_required_minor !== null && (
                        <div className="flex items-center justify-between py-2 border-b border-gray-100">
                            <span className="text-customer-text text-customer-caption">Down payment required</span>
                            <span className="text-customer-text font-semibold text-customer-caption">
                                {formatNaira(repair.down_payment_required_minor)}
                            </span>
                        </div>
                    )}
                    <div className="flex items-center justify-between py-2 border-b border-gray-100">
                        <span className="text-customer-text text-customer-caption">Payment status</span>
                        <span className="text-customer-text font-semibold text-customer-caption capitalize">
                            {repair.financial_status.replace(/_/g, ' ')}
                        </span>
                    </div>
                    <div className="flex items-center justify-between pt-2">
                        <span className="text-customer-text text-customer-caption">Estimated collection</span>
                        <span className="text-customer-text font-semibold text-customer-caption">
                            {repair.estimated_collection_date ? new Date(repair.estimated_collection_date).toLocaleDateString() : 'TBD'}
                        </span>
                    </div>
                </div>

                {repair.diagnoses.length > 0 && (
                    <div className="bg-white rounded-customer-card shadow-customer-soft p-4">
                        <h2 className="font-bold text-customer-text mb-2">Diagnosis</h2>
                        {repair.diagnoses.map((diagnosis, index) => (
                            <div key={index} className="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                                <span className="text-customer-text text-customer-caption capitalize">{diagnosis.component.replace(/_/g, ' ')}</span>
                                <span className="text-customer-text2 text-customer-caption capitalize">{diagnosis.condition.replace(/_/g, ' ')}</span>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </CustomerShell>
    );
}
