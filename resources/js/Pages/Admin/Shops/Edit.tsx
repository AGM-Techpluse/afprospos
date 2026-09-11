import { FormEvent } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AdminShell from '../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../Components/Admin/AdminPageHead';
import AdminInput from '../../../Components/Admin/Forms/AdminInput';
import AdminButton from '../../../Components/Admin/AdminButton';
import ShopPreviewCard from '../../../Components/Admin/ShopPreviewCard';

type Shop = {
    id: number;
    name: string;
    sku_prefix_code: string;
    address: string;
    contact_phone: string;
    contact_email: string;
    status: 'active' | 'inactive';
};

export default function ShopEdit({ shop }: { shop: Shop }) {
    const { data, setData, put, processing, errors } = useForm({
        name: shop.name,
        sku_prefix_code: shop.sku_prefix_code,
        address: shop.address,
        contact_phone: shop.contact_phone,
        contact_email: shop.contact_email,
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        put(`/admin/shops/${shop.id}`);
    }

    return (
        <AdminShell>
            <Head title={`Edit ${shop.name}`} />

            <AdminPageHead
                title={`Edit ${shop.name}`}
                description="Update this shop's details."
                backHref={`/admin/shops/${shop.id}`}
                backLabel="Back to shop"
            />

            <div className="grid gap-8 lg:grid-cols-[minmax(0,640px)_320px] items-start max-w-[992px] mx-auto">
                <form onSubmit={submit}>
                    <AdminInput
                        label="Name"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        error={errors.name}
                        required
                    />
                    <AdminInput
                        label="SKU prefix code"
                        value={data.sku_prefix_code}
                        onChange={(e) => setData('sku_prefix_code', e.target.value.toUpperCase())}
                        error={errors.sku_prefix_code}
                        maxLength={10}
                        required
                    />
                    <AdminInput
                        label="Address"
                        value={data.address}
                        onChange={(e) => setData('address', e.target.value)}
                        error={errors.address}
                        required
                    />
                    <AdminInput
                        label="Contact phone"
                        value={data.contact_phone}
                        onChange={(e) => setData('contact_phone', e.target.value)}
                        error={errors.contact_phone}
                        required
                    />
                    <AdminInput
                        label="Contact email"
                        type="email"
                        value={data.contact_email}
                        onChange={(e) => setData('contact_email', e.target.value)}
                        error={errors.contact_email}
                        required
                    />

                    <div className="flex items-center gap-2">
                        <AdminButton type="submit" isLoading={processing}>
                            Save changes
                        </AdminButton>
                        <AdminButton type="button" variant="neutral" onClick={() => router.visit(`/admin/shops/${shop.id}`)}>
                            Cancel
                        </AdminButton>
                    </div>
                </form>

                <div className="lg:sticky lg:top-6">
                    <ShopPreviewCard
                        name={data.name}
                        skuPrefixCode={data.sku_prefix_code}
                        address={data.address}
                        contactPhone={data.contact_phone}
                        contactEmail={data.contact_email}
                        status={shop.status}
                    />
                </div>
            </div>
        </AdminShell>
    );
}
