import { FormEvent, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminShell from '../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../Components/Admin/AdminPageHead';
import AdminButton from '../../../../Components/Admin/AdminButton';
import Icon from '../../../../Components/Icons/Icon';
import { ProblemTagList, DeviceProblemTag } from '../../../../Features/Repairs/DeviceCatalogFaultEditor';

type DeviceBrand = { id: number; name: string; sort_order: number; model_count: number };
type DeviceType = {
    id: number;
    label: string;
    icon: string;
    sort_order: number;
    brands: DeviceBrand[];
    problem_tags: DeviceProblemTag[];
};

const inputClass =
    'border border-admin-border-strong rounded-admin-button px-2.5 py-1.5 text-xs outline-none focus:border-admin-blue focus:ring-2 focus:ring-admin-focus bg-white';

function QuickAddBrand({ deviceTypeId, sortOrder }: { deviceTypeId: number; sortOrder: number }) {
    const [name, setName] = useState('');

    function submit(e: FormEvent) {
        e.preventDefault();
        if (!name.trim()) return;
        router.post(
            '/admin/settings/device-catalog/brands',
            { device_type_id: deviceTypeId, name: name.trim(), sort_order: sortOrder },
            { preserveScroll: true, showProgress: false, onSuccess: () => setName('') },
        );
    }

    return (
        <form onSubmit={submit} className="flex items-center gap-1.5 mt-2">
            <input value={name} onChange={(e) => setName(e.target.value)} placeholder="Add a brand…" className={`${inputClass} min-w-0 flex-1`} />
            <AdminButton type="submit" variant="neutral">Add</AdminButton>
        </form>
    );
}

export default function DeviceCatalogTypeShow({ deviceType }: { deviceType: DeviceType }) {
    function removeBrand(id: number) {
        router.delete(`/admin/settings/device-catalog/brands/${id}`, { preserveScroll: true, showProgress: false });
    }

    return (
        <AdminShell>
            <Head title={deviceType.label} />

            <Link href="/admin/settings/device-catalog" className="inline-flex items-center gap-1 text-xs font-semibold text-admin-blue mb-3">
                <Icon name="chevron-left" />
                Back to device types
            </Link>

            <AdminPageHead title={deviceType.label} description="Brands offered under this type, and the default fixable faults every model falls back to." />

            <div className="grid gap-6 lg:grid-cols-2 items-start max-w-5xl">
                <div className="bg-admin-surface border border-admin-border rounded-admin-card p-5">
                    <div className="text-sm font-semibold text-admin-text mb-3">Brands</div>
                    {deviceType.brands.length === 0 ? (
                        <p className="text-sm text-admin-text3">No brands yet.</p>
                    ) : (
                        <ul className="flex flex-col gap-2">
                            {deviceType.brands.map((brand) => (
                                <li key={brand.id} className="border border-admin-border rounded-admin-card p-3 flex items-center justify-between gap-2">
                                    <div className="flex-1 min-w-0">
                                        <div className="text-sm font-semibold text-admin-text">{brand.name}</div>
                                        <div className="text-xs text-admin-text3">{brand.model_count} model{brand.model_count === 1 ? '' : 's'}</div>
                                    </div>
                                    <div className="flex items-center gap-2 shrink-0">
                                        <AdminButton type="button" onClick={() => router.visit(`/admin/settings/device-catalog/brands/${brand.id}`)}>
                                            <Icon name="box-arrow-up-right" className="mr-1.5" />
                                            View
                                        </AdminButton>
                                        <button type="button" onClick={() => removeBrand(brand.id)} className="text-admin-text3 hover:text-admin-red p-1" aria-label={`Remove ${brand.name}`}>
                                            <Icon name="x" />
                                        </button>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                    <QuickAddBrand deviceTypeId={deviceType.id} sortOrder={deviceType.brands.length} />
                </div>

                <div className="bg-admin-surface border border-admin-border rounded-admin-card p-5">
                    <div className="text-sm font-semibold text-admin-text mb-1">Fixable problems (default)</div>
                    <p className="text-xs text-admin-text3 mb-3">Applies to any model of this type with no fault overrides of its own.</p>
                    <ProblemTagList tags={deviceType.problem_tags} scope={{ device_type_id: deviceType.id }} addPlaceholder="Add a problem tag…" />
                </div>
            </div>
        </AdminShell>
    );
}
