import { FormEvent } from 'react';
import { useForm, Head } from '@inertiajs/react';
import CustomerAuthShell from '../../Components/Customer/CustomerAuthShell';
import CustomerInput from '../../Components/Customer/Forms/CustomerInput';
import CustomerButton from '../../Components/Customer/Forms/CustomerButton';

export default function CustomerRegister() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        phone: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        post('/customer/register');
    }

    return (
        <CustomerAuthShell>
            <Head title="Sign up" />
            
            <div className="max-w-md w-full mx-auto">
                <h1 className="text-xl font-bold text-customer-text mb-6">
                    Create your account
                </h1>

                <form onSubmit={submit}>
                    <CustomerInput
                        label="Full name"
                        type="text"
                        required
                        value={data.name}
                        onChange={(e: any) => setData('name', e.target.value)}
                        error={errors.name}
                        placeholder="e.g. Aisha Bello"
                    />

                    <CustomerInput
                        label="Phone number"
                        type="text"
                        required
                        value={data.phone}
                        onChange={(e: any) => setData('phone', e.target.value)}
                        error={errors.phone}
                        placeholder="e.g. 08012345678"
                    />

                    <CustomerInput
                        label="Email address"
                        type="email"
                        required
                        value={data.email}
                        onChange={(e: any) => setData('email', e.target.value)}
                        error={errors.email}
                        placeholder="e.g. customer@example.com"
                    />

                    <CustomerInput
                        label="Password"
                        type="password"
                        required
                        value={data.password}
                        onChange={(e: any) => setData('password', e.target.value)}
                        error={errors.password}
                    />

                    <CustomerInput
                        label="Confirm password"
                        type="password"
                        required
                        value={data.password_confirmation}
                        onChange={(e: any) => setData('password_confirmation', e.target.value)}
                    />

                    <div className="mt-8">
                        <CustomerButton 
                            type="submit" 
                            className="w-full"
                            isLoading={processing}
                            loadingText="Creating account..."
                        >
                            Sign up
                        </CustomerButton>
                    </div>
                </form>
            </div>
        </CustomerAuthShell>
    );
}
