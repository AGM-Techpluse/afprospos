import { FormEvent, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { useConfirm } from '../../../../hooks/useConfirm';
import AdminShell from '../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../Components/Admin/AdminPageHead';
import AdminButton from '../../../../Components/Admin/AdminButton';
import AdminInfoCard from '../../../../Components/Admin/AdminInfoCard';
import SettingsSubNav from '../../../../Components/Admin/SettingsSubNav';
import Icon from '../../../../Components/Icons/Icon';
import IconPicker from '../../../../Components/Icons/IconPicker';

type DeviceType = { id: number; label: string; icon: string; sort_order: number; brand_count: number; model_count: number };

const inputClass =
    'border border-admin-border-strong rounded-admin-button px-2.5 py-1.5 text-xs outline-none focus:border-admin-blue focus:ring-2 focus:ring-admin-focus bg-white';

function DeviceTypeCard({ deviceType }: { deviceType: DeviceType }) {
    const confirm = useConfirm();
    const [editing, setEditing] = useState(false);
    const [label, setLabel] = useState(deviceType.label);
    const [icon, setIcon] = useState(deviceType.icon);

    function saveType(e: FormEvent) {
        e.preventDefault();
        e.stopPropagation();
        router.put(
            `/admin/settings/device-catalog/types/${deviceType.id}`,
            { label, icon, sort_order: deviceType.sort_order },
            { preserveScroll: true, showProgress: false, onSuccess: () => setEditing(false) },
        );
    }

    async function deleteType(e: React.MouseEvent) {
        e.stopPropagation();
        const confirmed = await confirm({
            kind: 'danger',
            title: `Delete "${deviceType.label}"?`,
            body: 'This also removes its brands, models, and problem tags. Repairs already created keep their own device details — nothing on an existing repair job changes.',
            confirmLabel: 'Delete device type',
        });
        if (confirmed) {
            router.delete(`/admin/settings/device-catalog/types/${deviceType.id}`, { preserveScroll: true, showProgress: false });
        }
    }

    if (editing) {
        return (
            <div className="bg-admin-surface border border-admin-border rounded-admin-card p-5">
                <form onSubmit={saveType} className="flex flex-col gap-2">
                    <IconPicker value={icon} onChange={setIcon} />
                    <input value={label} onChange={(e) => setLabel(e.target.value)} className={inputClass} />
                    <div className="flex items-center gap-2">
                        <AdminButton type="submit" variant="neutral">Save</AdminButton>
                        <AdminButton type="button" variant="neutral" onClick={() => setEditing(false)}>Cancel</AdminButton>
                    </div>
                </form>
            </div>
        );
    }

    return (
        <AdminInfoCard
            title={deviceType.label}
            icon={deviceType.icon}
            actions={
                <div className="flex items-center gap-1">
                    <button type="button" onClick={(e) => { e.stopPropagation(); setEditing(true); }} className="text-admin-text3 hover:text-admin-blue p-1" aria-label="Edit device type">
                        <Icon name="pencil" />
                    </button>
                    <button type="button" onClick={deleteType} className="text-admin-text3 hover:text-admin-red p-1" aria-label="Delete device type">
                        <Icon name="trash" />
                    </button>
                </div>
            }
        >
            <div className="flex items-center justify-between gap-3">
                <span className="text-sm text-admin-text2">
                    {deviceType.brand_count} brand{deviceType.brand_count === 1 ? '' : 's'} · {deviceType.model_count} model{deviceType.model_count === 1 ? '' : 's'}
                </span>
                <AdminButton
                    type="button"
                    onClick={() => router.visit(`/admin/settings/device-catalog/types/${deviceType.id}`)}
                >
                    <Icon name="box-arrow-up-right" className="mr-1.5" />
                    View
                </AdminButton>
            </div>
        </AdminInfoCard>
    );
}

function QuickAddDeviceType({ sortOrder }: { sortOrder: number }) {
    const [open, setOpen] = useState(false);
    const [label, setLabel] = useState('');
    const [icon, setIcon] = useState('device-hdd');

    function submit(e: FormEvent) {
        e.preventDefault();
        if (!label.trim()) return;
        router.post(
            '/admin/settings/device-catalog/types',
            { label: label.trim(), icon: icon || 'device-hdd', sort_order: sortOrder },
            { preserveScroll: true, showProgress: false, onSuccess: () => { setLabel(''); setIcon('device-hdd'); setOpen(false); } },
        );
    }

    if (!open) {
        return (
            <button
                type="button"
                onClick={() => setOpen(true)}
                className="flex flex-col items-center justify-center gap-2 border border-dashed border-admin-border-strong rounded-admin-card p-4 text-admin-text3 hover:border-admin-blue hover:text-admin-blue transition-colors min-h-[104px]"
            >
                <Icon name="plus-lg" className="text-lg" />
                <span className="text-xs font-semibold">Add device type</span>
            </button>
        );
    }

    return (
        <form onSubmit={submit} className="bg-admin-surface border border-admin-border rounded-admin-card p-4 flex flex-col gap-2">
            <IconPicker value={icon} onChange={setIcon} />
            <input value={label} onChange={(e) => setLabel(e.target.value)} placeholder="e.g. Drones" autoFocus className={inputClass} />
            <div className="flex items-center gap-2">
                <AdminButton type="submit" variant="neutral">Add</AdminButton>
                <AdminButton type="button" variant="neutral" onClick={() => setOpen(false)}>Cancel</AdminButton>
            </div>
        </form>
    );
}

interface DeviceCatalogIndexProps {
    deviceTypes: DeviceType[];
}

export default function DeviceCatalogIndex({ deviceTypes }: DeviceCatalogIndexProps) {
    return (
        <AdminShell>
            <Head title="Device catalog" />

            <AdminPageHead
                title="Device catalog"
                description="Configure the device types, brands, and models offered during repair intake, and each model's fixable problems/commission."
                actions={
                    <AdminButton variant="neutral" onClick={() => router.visit('/admin/settings/device-catalog/import')}>
                        <Icon name="cloud-arrow-up" className="mr-1.5" />
                        Import catalog
                    </AdminButton>
                }
            />

            <SettingsSubNav />

            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                {deviceTypes.map((deviceType) => (
                    <DeviceTypeCard key={deviceType.id} deviceType={deviceType} />
                ))}
                <QuickAddDeviceType sortOrder={deviceTypes.length} />
            </div>
        </AdminShell>
    );
}
