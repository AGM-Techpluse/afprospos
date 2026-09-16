import { Head } from '@inertiajs/react';
import AdminShell from '../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../Components/Admin/AdminPageHead';
import DeviceCatalogManager, { DeviceType } from '../../../../Features/Repairs/DeviceCatalogManager';

interface DeviceCatalogIndexProps {
    deviceTypes: DeviceType[];
}

export default function DeviceCatalogIndex({ deviceTypes }: DeviceCatalogIndexProps) {
    return (
        <AdminShell>
            <Head title="Device catalog" />

            <AdminPageHead
                title="Device catalog"
                description="Configure the device types, brands, and fixable problems offered during repair intake, and which parts are suggested for each problem."
            />

            <div className="max-w-4xl">
                <DeviceCatalogManager deviceTypes={deviceTypes} />
            </div>
        </AdminShell>
    );
}
