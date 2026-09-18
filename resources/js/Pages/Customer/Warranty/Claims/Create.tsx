import { FormEvent } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import CustomerShell from '../../../../Components/Customer/CustomerShell';
import CustomerSelect from '../../../../Components/Customer/Forms/CustomerSelect';
import CustomerButton from '../../../../Components/Customer/CustomerButton';
import Icon from '../../../../Components/Icons/Icon';

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

            <h1 className="font-customer-display font-extrabold text-customer-2xl text-customer-text mb-1">File a warranty claim</h1>
            <p className="text-customer-text2 text-customer-caption mb-6">
                Tell us which warranty policy applies — our team will assess your claim and get back to you.
            </p>

            {!hasOrigin ? (
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-2xl">
                    <div className="relative rounded-customer-card border border-customer-blue/40 bg-customer-blue-soft p-5 overflow-hidden">
                        <span className="absolute -right-6 -bottom-6 w-24 h-24 rounded-full bg-white/25" aria-hidden="true" />
                        <span className="relative w-10 h-10 rounded-customer-pill bg-white/70 flex items-center justify-center text-customer-blue mb-3">
                            <Icon name="bag-check" />
                        </span>
                        <h2 className="relative font-bold text-customer-text mb-1">Claim on a purchase</h2>
                        <p className="relative text-customer-caption text-customer-text2 mb-4">
                            Got a faulty phone or accessory you bought from us? Open the order and file a claim from there.
                        </p>
                        <Link
                            href="/customer/orders"
                            className="relative inline-flex items-center gap-1.5 rounded-customer-pill bg-customer-blue text-white font-semibold text-customer-caption px-4 py-2"
                        >
                            View your orders
                            <Icon name="arrow-right" />
                        </Link>
                    </div>

                    <div className="relative rounded-customer-card border border-customer-green/40 bg-customer-green-soft p-5 overflow-hidden">
                        <span className="absolute -right-6 -bottom-6 w-24 h-24 rounded-full bg-white/25" aria-hidden="true" />
                        <span className="relative w-10 h-10 rounded-customer-pill bg-white/70 flex items-center justify-center text-customer-green mb-3">
                            <Icon name="tools" />
                        </span>
                        <h2 className="relative font-bold text-customer-text mb-1">Claim on a repair</h2>
                        <p className="relative text-customer-caption text-customer-text2 mb-4">
                            Had a repair done and the fix didn't hold? Open the repair and file a claim from there.
                        </p>
                        <Link
                            href="/customer/repairs"
                            className="relative inline-flex items-center gap-1.5 rounded-customer-pill bg-customer-green text-white font-semibold text-customer-caption px-4 py-2"
                        >
                            View your repairs
                            <Icon name="arrow-right" />
                        </Link>
                    </div>
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
