import { Head } from '@inertiajs/react';
import AdminShell from '../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../Components/Admin/AdminPageHead';
import SettingsSubNav from '../../../../Components/Admin/SettingsSubNav';
import CommonComponentManager, { CommonComponent } from '../../../../Features/Repairs/CommonComponentManager';

interface CommonComponentsIndexProps {
    components: CommonComponent[];
}

export default function CommonComponentsIndex({ components }: CommonComponentsIndexProps) {
    return (
        <AdminShell>
            <Head title="Common components" />

            <AdminPageHead
                title="Common components"
                description="Configure the quick-check component list offered when a technician opens a new diagnosis case, and any commission earned for fixing one."
            />

            <SettingsSubNav />

            <div className="max-w-2xl">
                <CommonComponentManager components={components} />
            </div>
        </AdminShell>
    );
}
