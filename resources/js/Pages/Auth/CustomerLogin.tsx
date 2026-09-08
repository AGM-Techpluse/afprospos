import { FormEvent } from 'react';
import { useForm, Head } from '@inertiajs/react';
import CustomerAuthShell from '../../Components/Customer/CustomerAuthShell';
import CustomerInput from '../../Components/Customer/Forms/CustomerInput';
import CustomerButton from '../../Components/Customer/Forms/CustomerButton';

export default function CustomerLogin() {
    const { data, setData, post, processing, errors } = useForm({
        phone: '',
        password: '',
        remember: false,
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        post('/customer/login');
    }

    return (
        <CustomerAuthShell>
            <Head title="Sign in" />
            
            <div className="max-w-md w-full mx-auto">
                <h1 className="text-xl font-bold text-customer-text mb-6">
                    Sign in
                </h1>

                <form onSubmit={submit}>
                    <CustomerInput
                        label="Phone number"
                        type="text"
                        value={data.phone}
                        onChange={(e: any) => setData('phone', e.target.value)}
                        error={errors.phone}
                        placeholder="e.g. 08012345678"
                    />

                    <CustomerInput
                        label="Password"
                        type="password"
                        value={data.password}
                        onChange={(e: any) => setData('password', e.target.value)}
                        error={errors.password}
                    />

                    <div className="mt-8">
                        <CustomerButton 
                            type="submit" 
                            className="w-full"
                            isLoading={processing}
                        >
                            Sign in
                        </CustomerButton>
                    </div>
                </form>
            </div>
        </CustomerAuthShell>
    );
}
