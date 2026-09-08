import { FormEvent } from 'react';
import { useForm } from '@inertiajs/react';

/**
 * Deliberately unstyled beyond basic layout — Phase 2 (Shared UI Design
 * System) supplies the actual visual system these forms will consume.
 * Phase 1's job is a provably working, server-enforced login path.
 */
export default function StaffLogin() {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        post('/staff/login');
    }

    return (
        <form onSubmit={submit}>
            <h1>Staff sign in</h1>

            <label>
                Email
                <input
                    type="email"
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                />
            </label>
            {errors.email && <div role="alert">{errors.email}</div>}

            <label>
                Password
                <input
                    type="password"
                    value={data.password}
                    onChange={(e) => setData('password', e.target.value)}
                />
            </label>

            <label>
                <input
                    type="checkbox"
                    checked={data.remember}
                    onChange={(e) => setData('remember', e.target.checked)}
                />
                Remember me
            </label>

            <button type="submit" disabled={processing}>
                Sign in
            </button>
        </form>
    );
}
