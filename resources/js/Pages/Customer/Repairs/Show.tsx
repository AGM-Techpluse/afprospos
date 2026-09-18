import { Head } from '@inertiajs/react';
import CustomerShell from '../../../Components/Customer/CustomerShell';
import CustomerDetailCard from '../../../Components/Customer/CustomerDetailCard';
import Icon from '../../../Components/Icons/Icon';
import { formatNaira } from '../../../lib/money';

const STATUS_TONE: Record<string, 'success' | 'warning' | 'danger' | 'info'> = {
    completed: 'success',
    unrepairable: 'danger',
    expired_cancelled: 'danger',
    awaiting_authorization: 'warning',
    awaiting_parts: 'warning',
};

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

            {/* One wrapping div = one grid item, so this column stays in customer-content-grid's first track. */}
            <div className="customer-content-grid py-4 sm:py-0">
                <div>
                    <h1 className="font-customer-display font-extrabold text-customer-2xl text-customer-text mb-4">
                        {repair.device_make} {repair.device_model}
                    </h1>

                    <CustomerDetailCard
                        icon="tools"
                        title={`${repair.device_make} ${repair.device_model}`}
                        subtitle={`Repair #${repair.id}`}
                        badge={{ label: repair.repair_status.replace(/_/g, ' '), tone: STATUS_TONE[repair.repair_status] ?? 'info' }}
                        details={[
                            { label: 'Labour charge', value: formatNaira(repair.labour_charge_minor) },
                            {
                                label: 'Down payment',
                                value: repair.down_payment_required_minor !== null ? formatNaira(repair.down_payment_required_minor) : '—',
                            },
                            { label: 'Payment status', value: repair.financial_status.replace(/_/g, ' ') },
                            {
                                label: 'Estimated collection',
                                value: repair.estimated_collection_date ? new Date(repair.estimated_collection_date).toLocaleDateString() : 'TBD',
                            },
                        ]}
                        actions={
                            repair.repair_status === 'completed' ? (
                                <>
                                    <button
                                        type="button"
                                        onClick={() => window.print()}
                                        className="inline-flex items-center gap-1.5 rounded-customer-pill bg-customer-blue text-white font-semibold text-customer-caption px-3 py-1.5 print:hidden"
                                    >
                                        <Icon name="printer" />
                                        Print
                                    </button>
                                    <a
                                        href={`/customer/warranty/claims/create?repair=${repair.id}`}
                                        className="inline-flex items-center gap-1.5 rounded-customer-pill bg-customer-blue text-white font-semibold text-customer-caption px-3 py-1.5 print:hidden"
                                    >
                                        <Icon name="shield-exclamation" />
                                        File a claim
                                    </a>
                                </>
                            ) : undefined
                        }
                    />

                    {repair.diagnoses.length > 0 && (
                        <div className="bg-white rounded-customer-card shadow-customer-soft p-4 mt-4">
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
            </div>
        </CustomerShell>
    );
}
