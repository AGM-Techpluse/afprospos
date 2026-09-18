import { Head, router } from '@inertiajs/react';
import CustomerShell from '../../../../Components/Customer/CustomerShell';
import CustomerListItem from '../../../../Components/Customer/CustomerListItem';
import CustomerEmptyState from '../../../../Components/Customer/CustomerEmptyState';

type ReturnRequestRow = {
    id: number;
    sale_id: number;
    resolution_state: string;
    return_window_expires_at: string;
    created_at: string;
};

type Paginated<T> = { data: T[]; current_page: number; last_page: number; per_page: number; total: number };

const STATE_LABEL: Record<string, string> = {
    requested: 'Requested',
    under_assessment: 'Under assessment',
    approved: 'Approved',
    denied: 'Denied',
    resolved: 'Resolved',
};

export default function CustomerReturnRequestsIndex({ returns }: { returns: Paginated<ReturnRequestRow> }) {
    return (
        <CustomerShell>
            <Head title="Returns" />

            {/* One wrapping div = one grid item, so this column stays in customer-content-grid's first track. */}
            <div className="customer-content-grid py-4 sm:py-0">
                <div>
                    <div className="flex items-center justify-between mb-1">
                        <h1 className="font-customer-display font-extrabold text-customer-2xl text-customer-text">Your returns</h1>
                        <button
                            type="button"
                            onClick={() => router.visit('/customer/warranty/returns/create')}
                            className="text-customer-blue font-semibold text-customer-caption"
                        >
                            New return
                        </button>
                    </div>

                    <button
                        type="button"
                        onClick={() => router.visit('/customer/warranty/claims')}
                        className="text-customer-blue font-semibold text-customer-caption mb-4"
                    >
                        View your warranty claims →
                    </button>

                    {returns.data.length === 0 ? (
                        <CustomerEmptyState
                            icon="arrow-counterclockwise"
                            description="No returns yet — if you want to give back a recent purchase, you can start a return here."
                            className="bg-white rounded-customer-card shadow-customer-soft"
                        />
                    ) : (
                        <div className="bg-white rounded-customer-card shadow-customer-soft px-4 overflow-hidden">
                            {returns.data.map((returnRequest) => (
                                <CustomerListItem
                                    key={returnRequest.id}
                                    icon="arrow-counterclockwise"
                                    title={`Return #${returnRequest.id}`}
                                    subtitle={new Date(returnRequest.created_at).toLocaleDateString()}
                                    amount={STATE_LABEL[returnRequest.resolution_state] ?? returnRequest.resolution_state}
                                    bare
                                    details={[
                                        { label: 'Sale', value: `#${returnRequest.sale_id}` },
                                        { label: 'Return window', value: new Date(returnRequest.return_window_expires_at).toLocaleDateString() },
                                    ]}
                                    actions={[
                                        <button
                                            key="view"
                                            type="button"
                                            onClick={() => router.visit(`/customer/warranty/returns/${returnRequest.id}`)}
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
