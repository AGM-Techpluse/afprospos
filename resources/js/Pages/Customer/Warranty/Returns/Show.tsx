import { Head } from '@inertiajs/react';
import CustomerShell from '../../../../Components/Customer/CustomerShell';
import CustomerDetailCard from '../../../../Components/Customer/CustomerDetailCard';
import { formatNaira } from '../../../../lib/money';

interface Sale {
    id: number;
    invoice_number: string | null;
    total_minor: number;
    created_at: string;
}

interface ReturnRequestDetail {
    id: number;
    sale: Sale | null;
    resolution_state: string;
    return_window_expires_at: string;
    denial_reason: string | null;
    created_at: string;
}

const STATE_LABEL: Record<string, string> = {
    requested: 'Requested',
    under_assessment: 'Under assessment',
    approved: 'Approved',
    denied: 'Denied',
    resolved: 'Resolved',
};

const STATE_TONE: Record<string, 'success' | 'warning' | 'danger' | 'info'> = {
    requested: 'warning',
    under_assessment: 'warning',
    approved: 'info',
    denied: 'danger',
    resolved: 'success',
};

export default function CustomerReturnRequestShow({ returnRequest }: { returnRequest: ReturnRequestDetail }) {
    return (
        <CustomerShell>
            <Head title={`Return #${returnRequest.id}`} />

            {/* One wrapping div = one grid item, so this column stays in customer-content-grid's first track. */}
            <div className="customer-content-grid py-4 sm:py-0">
                <div>
                    <h1 className="font-customer-display font-extrabold text-customer-2xl text-customer-text mb-4">Return #{returnRequest.id}</h1>

                    <CustomerDetailCard
                        icon="arrow-counterclockwise"
                        title={`Return #${returnRequest.id}`}
                        subtitle={new Date(returnRequest.created_at).toLocaleDateString()}
                        badge={{ label: STATE_LABEL[returnRequest.resolution_state] ?? returnRequest.resolution_state, tone: STATE_TONE[returnRequest.resolution_state] ?? 'info' }}
                        details={[
                            { label: 'Purchase', value: returnRequest.sale ? (returnRequest.sale.invoice_number ?? `Sale #${returnRequest.sale.id}`) : '—' },
                            { label: 'Amount', value: returnRequest.sale ? formatNaira(returnRequest.sale.total_minor) : '—' },
                            { label: 'Return window', value: new Date(returnRequest.return_window_expires_at).toLocaleDateString() },
                            { label: 'Reason for denial', value: returnRequest.denial_reason ?? '—' },
                        ]}
                    />
                </div>
            </div>
        </CustomerShell>
    );
}
