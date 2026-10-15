import { FormEvent, useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AdminShell from '../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../Components/Admin/AdminPageHead';
import AdminButton from '../../../Components/Admin/AdminButton';
import AdminInput from '../../../Components/Admin/Forms/AdminInput';
import AdminSelect from '../../../Components/Admin/Forms/AdminSelect';
import AdminTextarea from '../../../Components/Admin/Forms/AdminTextarea';
import CustomerSearch, { CustomerSearchResult } from '../../../Features/Sales/CustomerSearch';
import DeviceLockInput, { DeviceLockType } from '../../../Features/Repairs/DeviceLockInput';
import DeviceTypePicker, { DeviceType } from '../../../Features/Repairs/DeviceTypePicker';
import CommonComponentsCard from '../../../Features/Repairs/CommonComponentsCard';

type Shop = { id: number; name: string; is_warehouse?: boolean };
type Technician = { id: number; name: string };

interface Props {
    shops: Shop[];
    technicians: Technician[];
    deviceCatalog: DeviceType[];
}

export default function DiagnosisCaseCreate({ shops, technicians, deviceCatalog }: Props) {
    const intakeShops = shops.filter((shop) => !shop.is_warehouse);
    const [customer, setCustomer] = useState<CustomerSearchResult | null>(null);
    const { data, setData, post, processing, errors } = useForm({
        shop_id: intakeShops[0]?.id.toString() ?? '',
        customer_id: '',
        device_model_id: '',
        diagnosis_technician_staff_id: '',
        device_make: '',
        device_model: '',
        reported_issue: '',
        device_imei_serial: '',
        device_lock_type: 'none' as DeviceLockType,
        device_lock_value: '',
        revealed_faults: [] as { device_problem_tag_id: number | null; label: string; notes: string }[],
    });

    function addCommonFaults(components: { label: string }[]) {
        const next = [...data.revealed_faults];
        components.forEach((component) => {
            if (!next.some((fault) => fault.label === component.label)) {
                next.push({ device_problem_tag_id: null, label: component.label, notes: '' });
            }
        });
        setData('revealed_faults', next);
    }

    function submit(e: FormEvent) {
        e.preventDefault();
        post('/admin/repairs/diagnosis-cases');
    }

    return (
        <AdminShell>
            <Head title="New diagnosis" />
            <AdminPageHead title="New diagnosis" description="Receive a device for diagnosis without opening repair work yet." />

            <div className="grid gap-8 lg:grid-cols-[minmax(0,1fr)_400px] items-start max-w-[942px] mx-auto">
                <form onSubmit={submit}>
                    <AdminSelect label="Shop" value={data.shop_id} onChange={(e) => setData('shop_id', e.target.value)} error={errors.shop_id}>
                        {intakeShops.map((shop) => <option key={shop.id} value={shop.id}>{shop.name}</option>)}
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

                    <AdminSelect label="Diagnosis technician" value={data.diagnosis_technician_staff_id} onChange={(e) => setData('diagnosis_technician_staff_id', e.target.value)} error={errors.diagnosis_technician_staff_id}>
                        <option value="">Assign later</option>
                        {technicians.map((technician) => <option key={technician.id} value={technician.id}>{technician.name}</option>)}
                    </AdminSelect>

                    <AdminInput label="Device make" value={data.device_make} onChange={(e) => setData('device_make', e.target.value)} error={errors.device_make} />
                    <AdminInput label="Device model" value={data.device_model} onChange={(e) => setData('device_model', e.target.value)} error={errors.device_model} />
                    <AdminInput label="IMEI / serial number (optional)" value={data.device_imei_serial} onChange={(e) => setData('device_imei_serial', e.target.value)} error={errors.device_imei_serial} />
                    <AdminTextarea label="Reported issue" value={data.reported_issue} onChange={(e) => setData('reported_issue', e.target.value)} error={errors.reported_issue} rows={3} />
                    <DeviceLockInput
                        lockType={data.device_lock_type}
                        lockValue={data.device_lock_value}
                        onChange={(type, value) => {
                            setData('device_lock_type', type);
                            setData('device_lock_value', value);
                        }}
                        error={errors.device_lock_value}
                    />

                    <AdminButton type="submit" isLoading={processing}>Create diagnosis case</AdminButton>
                </form>

                <div className="flex flex-col gap-4">
                    <div className="bg-admin-surface border border-admin-border rounded-admin-card p-4">
                        <DeviceTypePicker
                            deviceTypes={deviceCatalog}
                            selectedProblemTagIds={[]}
                            onToggleProblemTag={() => {}}
                            onSelectBrand={(deviceType, brand, model, modelId) => {
                                setData('device_make', brand || deviceType);
                                setData('device_model', model ?? '');
                                setData('device_model_id', modelId ? String(modelId) : '');
                            }}
                        />
                    </div>

                    <CommonComponentsCard
                        diagnosedComponents={data.revealed_faults.map((fault) => fault.label)}
                        onMarkSelected={addCommonFaults}
                    />
                </div>
            </div>
        </AdminShell>
    );
}
