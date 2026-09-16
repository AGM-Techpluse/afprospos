import { FormEvent, useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AdminShell from '../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../Components/Admin/AdminPageHead';
import AdminButton from '../../../Components/Admin/AdminButton';
import AdminSelect from '../../../Components/Admin/Forms/AdminSelect';
import AdminInput from '../../../Components/Admin/Forms/AdminInput';
import AdminTextarea from '../../../Components/Admin/Forms/AdminTextarea';
import CustomerSearch, { CustomerSearchResult } from '../../../Features/Sales/CustomerSearch';
import Icon from '../../../Components/Icons/Icon';
import DeviceTypePicker, { DeviceType } from '../../../Features/Repairs/DeviceTypePicker';
import DeviceLockInput, { DeviceLockType } from '../../../Features/Repairs/DeviceLockInput';

type Shop = { id: number; name: string };

interface RepairCreateProps {
    shops: Shop[];
    deviceCatalog: DeviceType[];
}

export default function RepairCreate({ shops, deviceCatalog }: RepairCreateProps) {
    const [customer, setCustomer] = useState<CustomerSearchResult | null>(null);
    const { data, setData, post, transform, processing, errors } = useForm({
        shop_id: shops[0]?.id.toString() ?? '',
        customer_id: '',
        device_make: '',
        device_model: '',
        reported_issue: '',
        device_imei_serial: '',
        device_lock_type: 'none' as DeviceLockType,
        device_lock_value: '',
        problem_tag_ids: [] as number[],
        labour_charge_naira: '',
    });

    const allProblemTags = deviceCatalog.flatMap((type) => type.problem_tags);
    const selectedProblemTags = data.problem_tag_ids
        .map((id) => allProblemTags.find((tag) => tag.id === id))
        .filter((tag): tag is { id: number; label: string } => Boolean(tag));

    function toggleProblemTag(id: number) {
        setData('problem_tag_ids', data.problem_tag_ids.includes(id) ? data.problem_tag_ids.filter((t) => t !== id) : [...data.problem_tag_ids, id]);
    }

    function submit(e: FormEvent) {
        e.preventDefault();
        transform((formData) => ({
            shop_id: formData.shop_id,
            customer_id: formData.customer_id,
            device_make: formData.device_make,
            device_model: formData.device_model,
            reported_issue: formData.reported_issue,
            device_imei_serial: formData.device_imei_serial,
            device_lock_type: formData.device_lock_type,
            device_lock_value: formData.device_lock_value,
            problem_tag_ids: formData.problem_tag_ids,
            labour_charge_minor: Math.round(parseFloat(formData.labour_charge_naira || '0') * 100),
        }));
        post('/admin/repairs');
    }

    return (
        <AdminShell>
            <Head title="New repair" />

            <AdminPageHead title="New repair" description="Intake a device for diagnosis and repair." />

            <div className="grid gap-8 lg:grid-cols-[minmax(0,1fr)_400px] items-start max-w-[942px] mx-auto">
                <form onSubmit={submit}>
                    <AdminSelect label="Shop" value={data.shop_id} onChange={(e) => setData('shop_id', e.target.value)} error={errors.shop_id}>
                        {shops.map((shop) => (
                            <option key={shop.id} value={shop.id}>
                                {shop.name}
                            </option>
                        ))}
                    </AdminSelect>

                    <CustomerSearch
                        value={customer}
                        onChange={(selected) => {
                            setCustomer(selected);
                            setData('customer_id', selected ? selected.id.toString() : '');
                        }}
                        searchUrl="/admin/repairs/customers/search"
                        error={errors.customer_id}
                    />

                    <AdminInput
                        label="Device make"
                        value={data.device_make}
                        onChange={(e) => setData('device_make', e.target.value)}
                        error={errors.device_make}
                        placeholder="e.g. Apple, Samsung"
                    />

                    <AdminInput
                        label="Device model"
                        value={data.device_model}
                        onChange={(e) => setData('device_model', e.target.value)}
                        error={errors.device_model}
                        placeholder="e.g. iPhone 12"
                    />

                    <AdminInput
                        label="IMEI / serial number (optional)"
                        value={data.device_imei_serial}
                        onChange={(e) => setData('device_imei_serial', e.target.value)}
                        error={errors.device_imei_serial}
                        placeholder="e.g. 356938035601001"
                    />

                    <AdminTextarea
                        label="Reported issue"
                        value={data.reported_issue}
                        onChange={(e) => setData('reported_issue', e.target.value)}
                        error={errors.reported_issue}
                        placeholder="What did the customer say is wrong with the device?"
                        rows={3}
                    />

                    <DeviceLockInput
                        lockType={data.device_lock_type}
                        lockValue={data.device_lock_value}
                        onChange={(type, value) => {
                            setData('device_lock_type', type);
                            setData('device_lock_value', value);
                        }}
                        error={errors.device_lock_value}
                    />

                    {selectedProblemTags.length > 0 && (
                        <div className="-mt-3 mb-5">
                            <div className="text-xs text-admin-text2 mb-1.5">Selected problems</div>
                            <div className="flex flex-wrap gap-1.5">
                                {selectedProblemTags.map((tag) => (
                                    <span key={tag.id} className="inline-flex items-center gap-1 bg-admin-blue-soft text-admin-blue text-xs font-semibold rounded-admin-badge px-2 py-1">
                                        {tag.label}
                                        <button type="button" onClick={() => toggleProblemTag(tag.id)} aria-label={`Remove ${tag.label}`}>
                                            <Icon name="x" />
                                        </button>
                                    </span>
                                ))}
                            </div>
                        </div>
                    )}

                    <AdminInput
                        label="Labour charge (₦)"
                        type="number"
                        step="0.01"
                        value={data.labour_charge_naira}
                        onChange={(e) => setData('labour_charge_naira', e.target.value)}
                        error={errors.labour_charge_minor}
                    />

                    <AdminButton type="submit" isLoading={processing}>
                        Create repair job
                    </AdminButton>
                </form>

                <div className="bg-admin-surface border border-admin-border rounded-admin-card p-4">
                    <DeviceTypePicker
                        deviceTypes={deviceCatalog}
                        selectedProblemTagIds={data.problem_tag_ids}
                        onToggleProblemTag={toggleProblemTag}
                        onSelectBrand={(deviceType, brand) => {
                            setData('device_make', brand || deviceType);
                            setData('problem_tag_ids', []);
                        }}
                    />
                </div>
            </div>
        </AdminShell>
    );
}
