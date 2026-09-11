import { FormEvent } from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import CustomerShell from '../../../Components/Customer/CustomerShell';
import CustomerInput from '../../../Components/Customer/Forms/CustomerInput';
import CustomerButton from '../../../Components/Customer/CustomerButton';
import { useToast } from '../../../hooks/useToast';

export default function CustomerProfileShow() {
    const { auth } = usePage().props as any;
    const { toast } = useToast();

    const { data, setData, put, processing, errors, reset } = useForm({
        name: auth?.customer?.name ?? '',
        phone: auth?.customer?.phone ?? '',
        email: auth?.customer?.email ?? '',
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        put('/customer/profile', {
            onSuccess: () => toast({ kind: 'success', title: 'Profile updated' }),
        });
    }

    return (
        <CustomerShell>
            <Head title="My profile" />

            <h1 className="font-bold text-customer-xl text-customer-text mb-1">My profile</h1>
            <p className="text-customer-text2 text-customer-caption mb-6">
                Keep your contact details up to date so we can reach you about repairs and orders.
            </p>

            {/* No elevated card wrapper — forms sit directly on the page (UI/UX §10.1). */}
            <form onSubmit={submit} className="max-w-md">
                <CustomerInput
                    label="Full name"
                    value={data.name}
                    onChange={(e: any) => setData('name', e.target.value)}
                    error={errors.name}
                />
                <CustomerInput
                    label="Phone number"
                    value={data.phone}
                    onChange={(e: any) => setData('phone', e.target.value)}
                    error={errors.phone}
                />
                <CustomerInput
                    label="Email"
                    type="email"
                    value={data.email}
                    onChange={(e: any) => setData('email', e.target.value)}
                    error={errors.email}
                />

                <div className="flex items-center gap-2 mt-2">
                    <CustomerButton type="submit" isLoading={processing}>
                        Save changes
                    </CustomerButton>
                    <CustomerButton type="button" variant="neutral" onClick={() => reset()}>
                        Cancel
                    </CustomerButton>
                </div>
            </form>
        </CustomerShell>
    );
}
