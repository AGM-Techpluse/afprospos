import { FormEvent, useEffect, useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AdminShell from '../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../Components/Admin/AdminPageHead';
import AdminButton from '../../../Components/Admin/AdminButton';
import AdminInput from '../../../Components/Admin/Forms/AdminInput';
import AdminSelect from '../../../Components/Admin/Forms/AdminSelect';
import AdminTextarea from '../../../Components/Admin/Forms/AdminTextarea';
import PhotoDropzone from '../../../Components/PhotoDropzone';
import Icon from '../../../Components/Icons/Icon';
import CustomerSearch, { CustomerSearchResult } from '../../../Features/Sales/CustomerSearch';
import DeviceLockInput, { DeviceLockType } from '../../../Features/Repairs/DeviceLockInput';
import DeviceTypePicker, { DeviceType } from '../../../Features/Repairs/DeviceTypePicker';
import { CommonComponentsPicker, CommonComponentOption } from '../../../Features/Repairs/CommonComponentsCard';

type Shop = { id: number; name: string; is_warehouse?: boolean };
type Technician = { id: number; name: string };

interface Props {
    shops: Shop[];
    technicians: Technician[];
    deviceCatalog: DeviceType[];
    commonComponents: CommonComponentOption[];
}

/** One "label: value" line inside a preview-card section. */
function PreviewRow({ label, value, dim = false }: { label: string; value: React.ReactNode; dim?: boolean }) {
    return (
        <div className="flex items-center justify-between gap-3 text-sm">
            <span className={dim ? 'text-white/70' : 'text-admin-text2'}>{label}</span>
            <span className={`font-medium text-right truncate ${dim ? 'text-white' : 'text-admin-text'}`}>{value}</span>
        </div>
    );
}

export default function DiagnosisCaseCreate({ shops, technicians, deviceCatalog, commonComponents }: Props) {
    const intakeShops = shops.filter((shop) => !shop.is_warehouse);
    const [customer, setCustomer] = useState<CustomerSearchResult | null>(null);
    /** Only for the preview card — device_make/device_model already carry brand/model for submission, but the type label itself isn't part of the payload. */
    const [deviceTypeLabel, setDeviceTypeLabel] = useState('');
    const { data, setData, post, processing, errors } = useForm({
        shop_id: intakeShops[0]?.id.toString() ?? '',
        customer_id: '',
        walk_in_name: '',
        walk_in_phone: '',
        walk_in_email: '',
        device_model_id: '',
        diagnosis_technician_staff_id: '',
        device_make: '',
        device_model: '',
        reported_issue: '',
        device_imei_serial: '',
        device_lock_type: 'none' as DeviceLockType,
        device_lock_value: '',
        revealed_faults: [] as { device_problem_tag_id: number | null; common_component_id: number | null; label: string; notes: string }[],
        photo: null as File | null,
    });

    const [photoPreviewUrl, setPhotoPreviewUrl] = useState<string | null>(null);

    useEffect(() => {
        if (!data.photo) {
            setPhotoPreviewUrl(null);

            return;
        }

        const url = URL.createObjectURL(data.photo);
        setPhotoPreviewUrl(url);

        return () => URL.revokeObjectURL(url);
    }, [data.photo]);

    function addCommonFaults(components: CommonComponentOption[]) {
        const next = [...data.revealed_faults];
        components.forEach((component) => {
            if (!next.some((fault) => fault.label === component.label)) {
                next.push({ device_problem_tag_id: null, common_component_id: component.id, label: component.label, notes: '' });
            }
        });
        setData('revealed_faults', next);
    }

    function submit(e: FormEvent) {
        e.preventDefault();
        post('/admin/repairs/diagnosis-cases');
    }

    const modelRequired = data.device_model_id === '';
    const technicianName = technicians.find((technician) => String(technician.id) === data.diagnosis_technician_staff_id)?.name ?? null;
    const displayName = customer?.name ?? (data.walk_in_name.trim() || null);
    const displayPhone = customer?.phone ?? (data.walk_in_phone.trim() || null);
    const displayEmail = customer?.email ?? (data.walk_in_email.trim() || null);

    return (
        <AdminShell>
            <Head title="New diagnosis" />
            <AdminPageHead title="New diagnosis" description="Receive a device for diagnosis without opening repair work yet." />

            <div className="grid grid-cols-1 gap-8 lg:grid-cols-[minmax(0,1fr)_400px] items-start max-w-[942px] mx-auto">
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
                        walkIn={{ name: data.walk_in_name, phone: data.walk_in_phone, email: data.walk_in_email }}
                        onWalkInChange={(walkIn) =>
                            setData({ ...data, walk_in_name: walkIn.name, walk_in_phone: walkIn.phone, walk_in_email: walkIn.email })
                        }
                    />

                    <AdminSelect label="Diagnosis technician" value={data.diagnosis_technician_staff_id} onChange={(e) => setData('diagnosis_technician_staff_id', e.target.value)} error={errors.diagnosis_technician_staff_id}>
                        <option value="">Assign later</option>
                        {technicians.map((technician) => <option key={technician.id} value={technician.id}>{technician.name}</option>)}
                    </AdminSelect>

                    <div className="bg-admin-surface border border-admin-border rounded-admin-card p-4 mb-4">
                        <DeviceTypePicker
                            deviceTypes={deviceCatalog}
                            onSelectModel={(deviceType, brand, model, modelId) => {
                                setDeviceTypeLabel(deviceType);
                                setData({ ...data, device_make: brand || deviceType, device_model: model, device_model_id: String(modelId) });
                            }}
                        />
                    </div>

                    <AdminInput label="IMEI / serial number (optional)" value={data.device_imei_serial} onChange={(e) => setData('device_imei_serial', e.target.value)} error={errors.device_imei_serial} />
                    <AdminTextarea label="Reported issue" value={data.reported_issue} onChange={(e) => setData('reported_issue', e.target.value)} error={errors.reported_issue} rows={3} />

                    <label className="block text-xs font-semibold text-admin-text mb-1.5">Photo of the device (optional)</label>
                    <div className="mb-4">
                        <PhotoDropzone
                            file={data.photo}
                            onSelect={(file) => setData('photo', file)}
                            error={errors.photo}
                            label="Select a photo to upload"
                        />
                    </div>
                    <DeviceLockInput
                        lockType={data.device_lock_type}
                        lockValue={data.device_lock_value}
                        onChange={(type, value) => {
                            setData('device_lock_type', type);
                            setData('device_lock_value', value);
                        }}
                        error={errors.device_lock_value}
                    />

                    {modelRequired && <p className="text-xs text-admin-red mb-2">Pick (or quick-add) a device model above before saving.</p>}
                    <AdminButton type="submit" isLoading={processing} disabled={modelRequired}>Create diagnosis case</AdminButton>
                </form>

                <div className="flex flex-col gap-4">
                    <CommonComponentsPicker
                        components={commonComponents}
                        diagnosedComponents={data.revealed_faults.map((fault) => fault.label)}
                        onMarkSelected={addCommonFaults}
                    />

                    <div className="bg-admin-surface border border-admin-border rounded-admin-card overflow-hidden">
                        {/* Customer */}
                        <div className="bg-admin-blue p-5">
                            <div className="w-12 h-12 rounded-full bg-white/15 text-white flex items-center justify-center text-2xl mb-3">
                                <Icon name="person-circle" />
                            </div>
                            <div className="flex flex-col gap-1">
                                <PreviewRow label="Fullname:" value={displayName ?? '—'} dim />
                                <PreviewRow label="Phone:" value={displayPhone ?? '—'} dim />
                                <PreviewRow label="Email:" value={displayEmail ?? '—'} dim />
                            </div>
                        </div>

                        {/* Device */}
                        <div className="p-5 border-t border-admin-border flex flex-col gap-1">
                            <PreviewRow label="Device type:" value={deviceTypeLabel || '—'} />
                            <PreviewRow label="Brand:" value={data.device_make || '—'} />
                            <PreviewRow label="Model:" value={data.device_model || '—'} />
                            <div className="flex items-center justify-between gap-3 text-sm">
                                <span className="text-admin-text2">Image:</span>
                                {photoPreviewUrl ? (
                                    <a href={photoPreviewUrl} target="_blank" rel="noopener noreferrer" className="text-xs font-semibold text-admin-blue">
                                        View
                                    </a>
                                ) : (
                                    <span className="text-admin-text3 text-xs">None</span>
                                )}
                            </div>
                            <PreviewRow label="IMEI:" value={data.device_imei_serial || '—'} />
                        </div>

                        {/* Reported issue */}
                        <div className="bg-admin-blue p-5 border-t border-admin-border">
                            <div className="text-xs font-semibold text-white/70 mb-2">Reported issue</div>
                            {data.reported_issue.trim() !== '' ? (
                                <p className="font-admin-handwriting text-white text-2xl leading-snug">
                                    &ldquo;{data.reported_issue}&rdquo;
                                </p>
                            ) : (
                                <p className="text-sm text-white/60 italic">Nothing reported yet.</p>
                            )}
                        </div>

                        {/* Technician */}
                        <div className="p-5 border-t border-admin-border">
                            <PreviewRow label="Technician:" value={technicianName ?? 'Unassigned'} />
                        </div>
                    </div>
                </div>
            </div>
        </AdminShell>
    );
}
