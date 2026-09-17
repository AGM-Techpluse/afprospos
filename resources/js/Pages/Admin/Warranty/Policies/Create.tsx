import { FormEvent, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AdminShell from '../../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../../Components/Admin/AdminPageHead';
import AdminInput from '../../../../Components/Admin/Forms/AdminInput';
import AdminSelect from '../../../../Components/Admin/Forms/AdminSelect';
import AdminTextarea from '../../../../Components/Admin/Forms/AdminTextarea';
import AdminButton from '../../../../Components/Admin/AdminButton';

const REMEDIES = [
    { key: 'repair', label: 'Repair' },
    { key: 'replace', label: 'Replace' },
    { key: 'refund', label: 'Refund' },
    { key: 'exchange', label: 'Exchange (not yet actionable — requires the Trade-In flow)' },
];

function linesToArray(value: string): string[] {
    return value.split('\n').map((line) => line.trim()).filter(Boolean);
}

export default function WarrantyPolicyCreate() {
    const { data, setData, post, transform, processing, errors } = useForm({
        name: '',
        coverage_duration_days: '365',
        coverage_start_point: 'sale_date',
        covered_scope: '',
        exclusions: '',
        available_remedies: [] as string[],
        coverage_extent: 'full',
    });
    const [remedyError, setRemedyError] = useState('');

    function toggleRemedy(key: string) {
        setData(
            'available_remedies',
            data.available_remedies.includes(key)
                ? data.available_remedies.filter((r) => r !== key)
                : [...data.available_remedies, key],
        );
    }

    function submit(e: FormEvent) {
        e.preventDefault();

        if (data.available_remedies.length === 0) {
            setRemedyError('Select at least one remedy.');
            return;
        }
        setRemedyError('');

        transform((formData) => ({
            ...formData,
            coverage_duration_days: parseInt(formData.coverage_duration_days, 10) || 0,
            covered_scope: linesToArray(formData.covered_scope),
            exclusions: linesToArray(formData.exclusions),
        }));
        post('/admin/warranty/policies');
    }

    return (
        <AdminShell>
            <Head title="New warranty policy" />

            <AdminPageHead
                title="New warranty policy"
                description="Define coverage duration, what's covered, exclusions, and available remedies."
                backHref="/admin/warranty/policies"
                backLabel="Back to policies"
            />

            <form onSubmit={submit} className="max-w-[640px]">
                <AdminInput
                    label="Name"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    error={errors.name}
                    required
                />
                <AdminInput
                    label="Coverage duration (days)"
                    type="number"
                    min={1}
                    value={data.coverage_duration_days}
                    onChange={(e) => setData('coverage_duration_days', e.target.value)}
                    error={errors.coverage_duration_days}
                    required
                />
                <AdminSelect
                    label="Coverage starts from"
                    value={data.coverage_start_point}
                    onChange={(e) => setData('coverage_start_point', e.target.value)}
                    error={errors.coverage_start_point}
                >
                    <option value="sale_date">Sale date</option>
                    <option value="collection_date">Repair collection date</option>
                </AdminSelect>
                <AdminSelect
                    label="Coverage extent"
                    value={data.coverage_extent}
                    onChange={(e) => setData('coverage_extent', e.target.value)}
                    error={errors.coverage_extent}
                >
                    <option value="full">Full</option>
                    <option value="percentage">Percentage</option>
                    <option value="fixed_amount">Fixed amount</option>
                    <option value="labour_only">Labour only</option>
                    <option value="parts_only">Parts only</option>
                </AdminSelect>
                <AdminTextarea
                    label="What's covered (one per line)"
                    value={data.covered_scope}
                    onChange={(e) => setData('covered_scope', e.target.value)}
                    error={errors.covered_scope}
                    placeholder={'Manufacturing defects\nInternal component failure'}
                />
                <AdminTextarea
                    label="Exclusions (one per line)"
                    value={data.exclusions}
                    onChange={(e) => setData('exclusions', e.target.value)}
                    error={errors.exclusions}
                    placeholder={'Screen breakage\nLiquid damage\nUnauthorized repair'}
                />

                <div className="mb-4">
                    <div className="text-xs font-semibold text-admin-text mb-[5px]">Available remedies</div>
                    <div className="flex flex-col gap-2">
                        {REMEDIES.map((remedy) => (
                            <label key={remedy.key} className="flex items-center gap-2 text-sm text-admin-text">
                                <input
                                    type="checkbox"
                                    checked={data.available_remedies.includes(remedy.key)}
                                    onChange={() => toggleRemedy(remedy.key)}
                                />
                                {remedy.label}
                            </label>
                        ))}
                    </div>
                    {(remedyError || errors.available_remedies) && (
                        <p className="mt-1 text-xs text-admin-red">{remedyError || errors.available_remedies}</p>
                    )}
                </div>

                <div className="flex items-center gap-2">
                    <AdminButton type="submit" isLoading={processing}>
                        Create policy
                    </AdminButton>
                    <AdminButton type="button" variant="neutral" onClick={() => router.visit('/admin/warranty/policies')}>
                        Cancel
                    </AdminButton>
                </div>
            </form>
        </AdminShell>
    );
}
