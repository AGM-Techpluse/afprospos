import { FormEvent } from 'react';
import { useForm, Head, usePage } from '@inertiajs/react';
import AdminInput from '../../../Components/Admin/Forms/AdminInput';
import AdminButton from '../../../Components/Admin/AdminButton';
import AdminAuthShell from '../../../Components/Admin/AdminAuthShell';
import AlertBanner from '../../../Components/Feedback/AlertBanner';

export default function StaffLogin() {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: false,
    });
    
    const { app_name, app_logo } = usePage().props as any;

    function submit(e: FormEvent) {
        e.preventDefault();
        post('/staff/login');
    }

    return (
        <AdminAuthShell>
            <Head title="Staff Login" />
            
            <div className="w-full max-w-sm">
                <div className="flex flex-col items-center mb-8">
                    <img src={app_logo} alt={app_name} className="h-10 w-10 mb-4" />
                    <h1 className="text-2xl font-bold text-admin-text">Staff Login</h1>
                    <p className="text-admin-text2 text-sm mt-1">Sign in to your account</p>
                </div>

                <form onSubmit={submit}>
                    {errors.auth && (
                        <AlertBanner kind="danger" className="mb-5">
                            {errors.auth}
                        </AlertBanner>
                    )}

                    <AdminInput
                        label="Email address"
                        type="email"
                        value={data.email}
                        onChange={(e: any) => setData('email', e.target.value)}
                        error={errors.email}
                        placeholder="e.g. staff@afprospos.com"
                    />

                    <AdminInput
                        label="Password"
                        type="password"
                        value={data.password}
                        onChange={(e: any) => setData('password', e.target.value)}
                        error={errors.password}
                    />

                    <div className="flex items-center mb-6">
                        <input
                            type="checkbox"
                            id="remember"
                            checked={data.remember}
                            onChange={(e: any) => setData('remember', e.target.checked)}
                            className="rounded border-admin-border text-admin-blue focus:ring-admin-blue"
                        />
                        <label htmlFor="remember" className="ml-2 text-sm text-admin-text2">
                            Remember me
                        </label>
                    </div>

                    <AdminButton 
                        type="submit" 
                        className="w-full"
                        isLoading={processing}
                    >
                        Sign in
                    </AdminButton>
                </form>
            </div>
        </AdminAuthShell>
    );
}
