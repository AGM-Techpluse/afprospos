import { FormEvent } from 'react';
import { useForm } from '@inertiajs/react';

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
        <form onSubmit={submit}>
            <h1>Sign in</h1>

            <label>
                Phone number
                <input
                    type="text"
                    value={data.phone}
                    onChange={(e) => setData('phone', e.target.value)}
                />
            </label>
            {errors.phone && <div role="alert">{errors.phone}</div>}

            <label>
                Password
                <input
                    type="password"
                    value={data.password}
                    onChange={(e) => setData('password', e.target.value)}
                />
            </label>

            <button type="submit" disabled={processing}>
                Sign in
            </button>
        </form>
    );
}
