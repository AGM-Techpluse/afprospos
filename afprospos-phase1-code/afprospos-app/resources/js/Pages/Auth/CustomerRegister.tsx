import { FormEvent } from 'react';
import { useForm } from '@inertiajs/react';

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
        <form onSubmit={submit}>
            <h1>Create your account</h1>

            <label>
                Name
                <input value={data.name} onChange={(e) => setData('name', e.target.value)} />
            </label>
            {errors.name && <div role="alert">{errors.name}</div>}

            <label>
                Phone number
                <input value={data.phone} onChange={(e) => setData('phone', e.target.value)} />
            </label>
            {errors.phone && <div role="alert">{errors.phone}</div>}

            <label>
                Email (optional)
                <input
                    type="email"
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                />
            </label>

            <label>
                Password
                <input
                    type="password"
                    value={data.password}
                    onChange={(e) => setData('password', e.target.value)}
                />
            </label>

            <label>
                Confirm password
                <input
                    type="password"
                    value={data.password_confirmation}
                    onChange={(e) => setData('password_confirmation', e.target.value)}
                />
            </label>

            <button type="submit" disabled={processing}>
                Create account
            </button>
        </form>
    );
}
