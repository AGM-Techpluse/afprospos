import { FormEvent, useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AdminShell from '../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../Components/Admin/AdminPageHead';
import AdminButton from '../../../../Components/Admin/AdminButton';
import AdminInput from '../../../../Components/Admin/Forms/AdminInput';
import AdminTextarea from '../../../../Components/Admin/Forms/AdminTextarea';
import CustomerSearch, { CustomerSearchResult } from '../../../../Features/Sales/CustomerSearch';

interface TradeInCreateProps {
    checkoutId: number | null;
}

export default function TradeInCreate({ checkoutId }: TradeInCreateProps) {
    const [customer, setCustomer] = useState<CustomerSearchResult | null>(null);
    const { data, setData, post, processing, errors } = useForm({
        customer_id: '',
        related_checkout_id: checkoutId?.toString() ?? '',
        device_make: '',
        device_model: '',
        device_imei: '',
        device_condition: '',
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        post('/admin/warranty/trade-ins');
    }

    return (
        <AdminShell>
            <Head title="New trade-in" />

            <AdminPageHead
                title="Record a trade-in"
                description="Capture the customer's device before assessing its value."
                backHref="/admin/warranty/trade-ins"
                backLabel="Back to trade-ins"
            />

            <form onSubmit={submit} className="bg-admin-surface border border-admin-border rounded-admin-card p-6 max-w-lg">
                <CustomerSearch
                    value={customer}
                    onChange={(selected) => {
                        setCustomer(selected);
                        setData('customer_id', selected ? selected.id.toString() : '');
                    }}
                    error={errors.customer_id}
                    searchUrl="/admin/warranty/trade-ins/search-customers"
                />

                {checkoutId && (
                    <p className="text-xs text-admin-text2 mb-4">
                        Linked to checkout #{checkoutId} — an approved value will apply as a credit to this checkout automatically.
                    </p>
                )}

                <AdminInput
                    label="Device make"
                    value={data.device_make}
                    onChange={(e) => setData('device_make', e.target.value)}
                    error={errors.device_make}
                    required
                />
                <AdminInput
                    label="Device model"
                    value={data.device_model}
                    onChange={(e) => setData('device_model', e.target.value)}
                    error={errors.device_model}
                    required
                />
                <AdminInput
                    label="IMEI (optional)"
                    value={data.device_imei}
                    onChange={(e) => setData('device_imei', e.target.value)}
                    error={errors.device_imei}
                />
                <AdminTextarea
                    label="Condition / functional & cosmetic notes"
                    value={data.device_condition}
                    onChange={(e) => setData('device_condition', e.target.value)}
                    error={errors.device_condition}
                    placeholder="Screen, battery health, body condition, functional test results…"
                    required
                />

                <AdminButton type="submit" isLoading={processing}>Record trade-in</AdminButton>
            </form>
        </AdminShell>
    );
}
