import { FormEvent } from 'react';
import { useForm } from '@inertiajs/react';
import AdminButton from '../../Components/Admin/AdminButton';
import AdminInput from '../../Components/Admin/Forms/AdminInput';
import { formatNaira } from '../../lib/money';

interface RepairCostCardProps {
    submitUrl: string;
    repairCostMinor: number | null;
    editable: boolean;
}

/** The customer-agreed repair price, set after diagnosis — distinct from the intake labour charge, and editable until the job closes. */
export default function RepairCostCard({ submitUrl, repairCostMinor, editable }: RepairCostCardProps) {
    const { data, setData, transform, post, processing, errors: formErrors } = useForm({
        repair_cost_naira: repairCostMinor !== null ? (repairCostMinor / 100).toString() : '',
    });
    // The server validates the minor-unit field actually posted (`repair_cost_minor`),
    // not the naira-denominated field this form binds inputs to — so the error key
    // never matches `useForm`'s inferred `data` shape even though it's a real key.
    const errors = formErrors as Record<string, string | undefined>;

    function submit(e: FormEvent) {
        e.preventDefault();
        transform((formData) => ({
            repair_cost_minor: Math.round(parseFloat(formData.repair_cost_naira || '0') * 100),
        }));
        post(submitUrl, { preserveScroll: true });
    }

    return (
        <div className="bg-admin-surface border border-admin-border rounded-admin-card p-6">
            <p className="text-xs text-admin-text3 mb-4">
                The estimated price for this repair, agreed with the customer after diagnosis — separate from any technician labour charge.
            </p>

            {repairCostMinor !== null && <p className="text-lg font-semibold text-admin-text mb-4">{formatNaira(repairCostMinor)}</p>}

            {editable && (
                <form onSubmit={submit} className="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div className="flex-1 min-w-0">
                        <AdminInput
                            label="Repair cost (₦)"
                            type="number"
                            step="0.01"
                            value={data.repair_cost_naira}
                            onChange={(e) => setData('repair_cost_naira', e.target.value)}
                            error={errors.repair_cost_minor}
                        />
                    </div>
                    <AdminButton type="submit" isLoading={processing} disabled={data.repair_cost_naira === ''}>
                        {repairCostMinor !== null ? 'Update repair cost' : 'Save repair cost'}
                    </AdminButton>
                </form>
            )}

            {!editable && repairCostMinor === null && <p className="text-xs text-admin-text3">Not yet estimated.</p>}
        </div>
    );
}
