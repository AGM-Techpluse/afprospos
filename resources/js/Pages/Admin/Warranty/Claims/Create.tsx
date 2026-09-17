import { FormEvent, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AdminShell from '../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../Components/Admin/AdminPageHead';
import AdminSelect from '../../../../Components/Admin/Forms/AdminSelect';
import AdminInput from '../../../../Components/Admin/Forms/AdminInput';
import AdminButton from '../../../../Components/Admin/AdminButton';
import CustomerSearch, { CustomerSearchResult } from '../../../../Features/Sales/CustomerSearch';

interface WarrantyPolicyOption {
    id: number;
    name: string;
}

interface WarrantyClaimCreateProps {
    policies: WarrantyPolicyOption[];
}

type OriginType = 'sale' | 'repair';

export default function WarrantyClaimCreate({ policies }: WarrantyClaimCreateProps) {
    const [customer, setCustomer] = useState<CustomerSearchResult | null>(null);
    const [originType, setOriginType] = useState<OriginType>('sale');
    const { data, setData, post, transform, processing, errors } = useForm({
        warranty_policy_id: policies[0]?.id.toString() ?? '',
        customer_id: '',
        origin_id: '',
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        transform((formData) => ({
            warranty_policy_id: formData.warranty_policy_id,
            customer_id: formData.customer_id,
            originating_sale_id: originType === 'sale' ? formData.origin_id : '',
            originating_repair_job_id: originType === 'repair' ? formData.origin_id : '',
        }));
        post('/admin/warranty/claims');
    }

    return (
        <AdminShell>
            <Head title="New warranty claim" />

            <AdminPageHead
                title="New warranty claim"
                description="Submit a claim on behalf of a customer."
                backHref="/admin/warranty/claims"
                backLabel="Back to claims"
            />

            <form onSubmit={submit} className="max-w-[560px]">
                <AdminSelect
                    label="Warranty policy"
                    value={data.warranty_policy_id}
                    onChange={(e) => setData('warranty_policy_id', e.target.value)}
                    error={errors.warranty_policy_id}
                >
                    {policies.map((policy) => (
                        <option key={policy.id} value={policy.id}>{policy.name}</option>
                    ))}
                </AdminSelect>

                <CustomerSearch
                    value={customer}
                    onChange={(selected) => {
                        setCustomer(selected);
                        setData('customer_id', selected ? selected.id.toString() : '');
                    }}
                    searchUrl="/admin/warranty/claims/search-customers"
                    error={errors.customer_id}
                />

                <AdminSelect
                    label="Claim originates from"
                    value={originType}
                    onChange={(e) => setOriginType(e.target.value as OriginType)}
                >
                    <option value="sale">A sale</option>
                    <option value="repair">A repair job</option>
                </AdminSelect>

                <AdminInput
                    label={originType === 'sale' ? 'Sale ID' : 'Repair job ID'}
                    type="number"
                    min={1}
                    value={data.origin_id}
                    onChange={(e) => setData('origin_id', e.target.value)}
                    error={errors.originating_sale_id || errors.originating_repair_job_id}
                    required
                />

                <div className="flex items-center gap-2">
                    <AdminButton type="submit" isLoading={processing}>
                        Submit claim
                    </AdminButton>
                    <AdminButton type="button" variant="neutral" onClick={() => router.visit('/admin/warranty/claims')}>
                        Cancel
                    </AdminButton>
                </div>
            </form>
        </AdminShell>
    );
}
