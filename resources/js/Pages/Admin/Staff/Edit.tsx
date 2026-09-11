import { FormEvent } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AdminShell from '../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../Components/Admin/AdminPageHead';
import AdminInput from '../../../Components/Admin/Forms/AdminInput';
import AdminButton from '../../../Components/Admin/AdminButton';
import StaffPreviewCard from '../../../Components/Admin/StaffPreviewCard';

type StaffMember = {
    id: number;
    name: string;
    email: string;
    phone: string;
    status: 'active' | 'deactivated';
    roles: string[];
};

export default function StaffEdit({ staffMember }: { staffMember: StaffMember }) {
    const { data, setData, put, processing, errors } = useForm({
        name: staffMember.name,
        phone: staffMember.phone,
        email: staffMember.email,
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        put(`/admin/staff/${staffMember.id}`);
    }

    return (
        <AdminShell>
            <Head title={`Edit ${staffMember.name}`} />

            <AdminPageHead title={`Edit ${staffMember.name}`} description="Update this staff member's contact details." />

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

                    <div className="flex items-center gap-2">
                        <AdminButton type="submit" isLoading={processing}>
                            Save changes
                        </AdminButton>
                        <AdminButton
                            type="button"
                            variant="neutral"
                            onClick={() => router.visit(`/admin/staff/${staffMember.id}`)}
                        >
                            Cancel
                        </AdminButton>
                    </div>
                </form>

                <div className="lg:sticky lg:top-6">
                    <StaffPreviewCard name={data.name} phone={data.phone} email={data.email} status={staffMember.status} />
                </div>
            </div>
        </AdminShell>
    );
}
