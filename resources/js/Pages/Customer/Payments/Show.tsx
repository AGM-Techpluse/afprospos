import { FormEvent } from 'react';
import { Head, useForm } from '@inertiajs/react';
import CustomerShell from '../../../Components/Customer/CustomerShell';
import CustomerButton from '../../../Components/Customer/CustomerButton';
import CustomerInput from '../../../Components/Customer/Forms/CustomerInput';
import PaymentStatusBadge from '../../../Components/Customer/PaymentStatusBadge';
import Icon from '../../../Components/Icons/Icon';
import { formatNaira } from '../../../lib/money';

type CheckoutView = {
    id: number;
    status: string;
    total_minor: number;
    reservation_expires_at: string;
};

type PendingTransaction = {
    id: number;
    status: string;
    method: string;
    amount_minor: number;
    provider_reference: string | null;
} | null;

type BankTransferInstructions = {
    account_name: string;
    account_number: string;
    bank_name: string;
};

interface CustomerPaymentShowProps {
    checkout: CheckoutView;
    pendingTransaction: PendingTransaction;
    bankTransferInstructions: BankTransferInstructions;
}

export default function CustomerPaymentShow({ checkout, pendingTransaction, bankTransferInstructions }: CustomerPaymentShowProps) {
    const { data, setData, post, processing, errors } = useForm({ transfer_reference: '' });

    function submit(e: FormEvent) {
        e.preventDefault();
        post(`/customer/payments/checkouts/${checkout.id}/bank-transfer`);
    }

    const isOpen = checkout.status === 'open';

    return (
        <CustomerShell>
            <Head title="Pay for your order" />

            <div className="max-w-md">
                <h1 className="font-customer-display font-extrabold text-customer-2xl text-customer-text mb-1">Pay for checkout #{checkout.id}</h1>
                <p className="font-mono text-customer-2xl font-extrabold text-customer-text mb-4">{formatNaira(checkout.total_minor)}</p>

                {!isOpen ? (
                    <div className="bg-white rounded-customer-card shadow-customer-soft p-4">
                        <PaymentStatusBadge status="confirmed" />
                        <p className="text-customer-caption text-customer-text2 mt-3">
                            This checkout has already been paid — check your order history for the receipt.
                        </p>
                    </div>
                ) : pendingTransaction ? (
                    <div className="bg-white rounded-customer-card shadow-customer-soft p-4">
                        <PaymentStatusBadge status={pendingTransaction.status} />
                        <p className="text-customer-caption text-customer-text2 mt-3">
                            We've recorded your bank transfer{pendingTransaction.provider_reference ? ` (ref: ${pendingTransaction.provider_reference})` : ''}. A
                            shop team member will confirm it against our bank statement shortly.
                        </p>
                    </div>
                ) : (
                    <>
                        <div className="bg-white rounded-customer-card shadow-customer-soft p-4 mb-4">
                            <p className="text-customer-caption font-bold text-customer-text mb-2">How would you like to pay?</p>
                            <div className="inline-flex items-center gap-1.5 rounded-customer-pill bg-customer-blue-soft text-customer-blue font-semibold text-customer-caption px-3 py-1.5 mb-3">
                                <Icon name="bank" />
                                Bank transfer
                            </div>
                            <p className="text-customer-caption text-customer-text2 mb-1">Send the exact amount to:</p>
                            <div className="text-customer-caption text-customer-text">
                                <div><span className="font-semibold">Account name:</span> {bankTransferInstructions.account_name}</div>
                                <div><span className="font-semibold">Account number:</span> {bankTransferInstructions.account_number}</div>
                                <div><span className="font-semibold">Bank:</span> {bankTransferInstructions.bank_name}</div>
                            </div>
                        </div>

                        {/* No elevated card wrapper — forms sit directly on the page (UI/UX §10.1). */}
                        <form onSubmit={submit}>
                            <CustomerInput
                                label="Transfer reference (optional)"
                                type="text"
                                value={data.transfer_reference}
                                onChange={(e) => setData('transfer_reference', e.target.value)}
                                placeholder="e.g. bank app confirmation code"
                                error={errors.transfer_reference}
                            />

                            <CustomerButton type="submit" isLoading={processing}>
                                I've made the transfer
                            </CustomerButton>
                        </form>
                    </>
                )}
            </div>
        </CustomerShell>
    );
}
