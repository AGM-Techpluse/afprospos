/** Labels mirror ADD §45's customer-facing PaymentStatus mapping verbatim — never render "Paid" for `payment_pending_confirmation`. */
const STATUS_LABEL: Record<string, string> = {
    pending: 'Waiting for payment',
    payment_pending_confirmation: 'Transfer received — awaiting confirmation',
    confirmed: 'Payment confirmed',
    disputed: 'Payment under review',
    exception: 'Payment requires assistance',
    refunded: 'Refunded',
};

const STATUS_CLASS: Record<string, string> = {
    pending: 'bg-customer-yellow-soft text-customer-yellow-text',
    payment_pending_confirmation: 'bg-customer-yellow-soft text-customer-yellow-text',
    confirmed: 'bg-customer-green-soft text-customer-green',
    disputed: 'bg-customer-red-soft text-customer-red',
    exception: 'bg-customer-red-soft text-customer-red',
    refunded: 'bg-customer-blue-soft text-customer-blue',
};

export default function PaymentStatusBadge({ status }: { status: string }) {
    return (
        <span className={`inline-flex items-center rounded-customer-pill px-3 py-1.5 font-semibold text-customer-caption ${STATUS_CLASS[status] ?? 'bg-customer-blue-soft text-customer-blue'}`}>
            {STATUS_LABEL[status] ?? status}
        </span>
    );
}
