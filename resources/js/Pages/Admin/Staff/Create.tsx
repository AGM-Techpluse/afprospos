import { FormEvent } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AdminShell from '../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../Components/Admin/AdminPageHead';
import AdminInput from '../../../Components/Admin/Forms/AdminInput';
import AdminButton from '../../../Components/Admin/AdminButton';
import StaffPreviewCard from '../../../Components/Admin/StaffPreviewCard';

type Shop = { id: number; name: string; sku_prefix_code: string };

interface CreateStaffProps {
    availableRoles: string[];
    shops: Shop[];
}

export default function StaffCreate({ availableRoles, shops }: CreateStaffProps) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        phone: '',
        email: '',
        password: '',
        password_confirmation: '',
        initial_role_names: [] as string[],
        initial_shop_ids: [] as number[],
    });

    function toggleRole(role: string) {
        setData(
            'initial_role_names',
            data.initial_role_names.includes(role)
                ? data.initial_role_names.filter((r) => r !== role)
                : [...data.initial_role_names, role],
        );
    }

    function toggleShop(shopId: number) {
        setData(
            'initial_shop_ids',
            data.initial_shop_ids.includes(shopId)
                ? data.initial_shop_ids.filter((id) => id !== shopId)
                : [...data.initial_shop_ids, shopId],
        );
    }

    function submit(e: FormEvent) {
        e.preventDefault();
        post('/admin/staff');
    }

    return (
        <AdminShell>
            <Head title="New staff member" />

            <AdminPageHead
                title="New staff member"
                description="Create a staff account and optionally assign starting roles and shop access."
                backHref="/admin/staff"
                backLabel="Back to staff"
            />

            <div className="grid gap-8 lg:grid-cols-[minmax(0,640px)_320px] items-start max-w-[992px] mx-auto">
                <form onSubmit={submit}>
                    <AdminInput
                        label="Full name"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        error={errors.name}
                        required
                    />
                    <AdminInput
                        label="Phone number"
                        value={data.phone}
                        onChange={(e) => setData('phone', e.target.value)}
                        error={errors.phone}
                        required
                    />
                    <AdminInput
                        label="Email"
                        type="email"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        error={errors.email}
                        required
                    />
                    <AdminInput
                        label="Password"
                        type="password"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        error={errors.password}
                        required
                    />
                    <AdminInput
                        label="Confirm password"
                        type="password"
                        value={data.password_confirmation}
                        onChange={(e) => setData('password_confirmation', e.target.value)}
                        required
                    />

                    <div className="mb-5">
                        <span className="block text-sm font-semibold text-admin-text mb-2">Initial roles</span>
                        <div className="flex flex-col gap-2">
                            {availableRoles.map((role) => (
                                <label key={role} className="flex items-center gap-2 text-sm text-admin-text2">
                                    <input
                                        type="checkbox"
                                        checked={data.initial_role_names.includes(role)}
                                        onChange={() => toggleRole(role)}
                                    />
                                    {role}
                                </label>
                            ))}
                        </div>
                    </div>

                    <div className="mb-6">
                        <span className="block text-sm font-semibold text-admin-text mb-2">Initial shop access</span>
                        <div className="flex flex-col gap-2">
                            {shops.map((shop) => (
                                <label key={shop.id} className="flex items-center gap-2 text-sm text-admin-text2">
                                    <input
                                        type="checkbox"
                                        checked={data.initial_shop_ids.includes(shop.id)}
                                        onChange={() => toggleShop(shop.id)}
                                    />
                                    {shop.name} ({shop.sku_prefix_code})
                                </label>
                            ))}
                        </div>
                    </div>

                    <div className="flex items-center gap-2">
                        <AdminButton type="submit" isLoading={processing}>
                            Create staff member
                        </AdminButton>
                        <AdminButton type="button" variant="neutral" onClick={() => router.visit('/admin/staff')}>
                            Cancel
                        </AdminButton>
                    </div>
                </form>

                <div className="lg:sticky lg:top-6">
                    <StaffPreviewCard name={data.name} phone={data.phone} email={data.email} roles={data.initial_role_names} />
                </div>
            </div>
        </AdminShell>
    );
}
