import { FormEvent } from 'react';
import { Head, useForm } from '@inertiajs/react';
import CustomerShell from '../../../../Components/Customer/CustomerShell';
import CustomerSelect from '../../../../Components/Customer/Forms/CustomerSelect';
import CustomerButton from '../../../../Components/Customer/CustomerButton';

interface WarrantyPolicyOption {
    id: number;
    name: string;
}

interface WarrantyClaimCreateProps {
    policies: WarrantyPolicyOption[];
    originatingSaleId: number | null;
    originatingRepairJobId: number | null;
}

export default function CustomerWarrantyClaimCreate({ policies, originatingSaleId, originatingRepairJobId }: WarrantyClaimCreateProps) {
    const { data, setData, post, processing, errors } = useForm({
        warranty_policy_id: policies[0]?.id.toString() ?? '',
        originating_sale_id: originatingSaleId?.toString() ?? '',
        originating_repair_job_id: originatingRepairJobId?.toString() ?? '',
    });

    const hasOrigin = originatingSaleId !== null || originatingRepairJobId !== null;

    function submit(e: FormEvent) {
        e.preventDefault();
        post('/customer/warranty/claims');
    }

    return (
        <CustomerShell>
            <Head title="File a warranty claim" />

            <h1 className="font-bold text-customer-xl text-customer-text mb-1">File a warranty claim</h1>
            <p className="text-customer-text2 text-customer-caption mb-6">
                Tell us which warranty policy applies — our team will assess your claim and get back to you.
            </p>

            {!hasOrigin ? (
                <div className="bg-white rounded-customer-card shadow-customer-soft p-4 max-w-md text-customer-caption text-customer-text2">
                    You can file a warranty claim from an order or repair on your{' '}
                    <a href="/customer/orders" className="text-customer-blue font-semibold">Orders</a> or{' '}
                    <a href="/customer/repairs" className="text-customer-blue font-semibold">Repairs</a> page.
                </div>
            ) : (
                /* No elevated card wrapper — forms sit directly on the page (UI/UX §10.1). */
                <form onSubmit={submit} className="max-w-md">
                    <p className="text-customer-caption text-customer-text2 mb-4">
                        Filing for {originatingSaleId !== null ? `Sale #${originatingSaleId}` : `Repair #${originatingRepairJobId}`}
                    </p>

                    <CustomerSelect
                        label="Warranty policy"
                        value={data.warranty_policy_id}
                        onChange={(e) => setData('warranty_policy_id', e.target.value)}
                        error={errors.warranty_policy_id}
                    >
                        {policies.map((policy) => (
                            <option key={policy.id} value={policy.id}>{policy.name}</option>
                        ))}
                    </CustomerSelect>

                    {(errors.originating_sale_id || errors.originating_repair_job_id) && (
                        <p className="text-customer-caption text-customer-red mb-4">
                            {errors.originating_sale_id || errors.originating_repair_job_id}
                        </p>
                    )}

                    <div className="flex items-center gap-2 mt-2">
                        <CustomerButton type="submit" isLoading={processing}>
                            Submit claim
                        </CustomerButton>
                    </div>
                </form>
            )}
        </CustomerShell>
    );
}
