import { FormEvent, useState } from 'react';
import { router } from '@inertiajs/react';
import AdminModal from '../../../../Components/Admin/AdminModal';
import AdminButton from '../../../../Components/Admin/AdminButton';
import AdminSelect from '../../../../Components/Admin/Forms/AdminSelect';
import type { MasterEntry } from '../../../../Features/Inventory/MasterSelect';
import { formatInventoryCondition } from '../../../../lib/inventoryCondition';

interface EditUnitConditionModalProps {
    onClose: () => void;
    unit: { id: number; imei: string; condition: string };
    conditions: MasterEntry[];
}

/**
 * Replaces condition editing's previous home — a full dropdown squeezed
 * into one table cell, which looked fine on paper but was genuinely
 * cramped next to five other columns. One field, so a modal is overkill
 * for the field itself, but it gives the IMEI and the picker room to
 * breathe instead of fighting the rest of the row for space.
 */
export default function EditUnitConditionModal({ onClose, unit, conditions }: EditUnitConditionModalProps) {
    const [condition, setCondition] = useState(unit.condition);
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState<string | undefined>(undefined);

    const knownCondition = conditions.some((entry) => entry.name === unit.condition);

    function submit(e: FormEvent) {
        e.preventDefault();
        setProcessing(true);
        router.post(
            `/admin/inventory/serialized-units/${unit.id}/condition`,
            { condition },
            {
                preserveScroll: true,
                onSuccess: () => onClose(),
                onError: (errors) => setError(errors.condition),
                onFinish: () => setProcessing(false),
            },
        );
    }

    return (
        <AdminModal open onClose={onClose} title="Edit condition" description={`IMEI ${unit.imei}`}>
            <form onSubmit={submit} className="pb-6 flex flex-col gap-4">
                <AdminSelect label="Condition" value={condition} onChange={(e) => setCondition(e.target.value)} error={error}>
                    {!knownCondition && (
                        <option value={unit.condition}>{formatInventoryCondition(unit.condition)} (not in catalog)</option>
                    )}
                    {conditions.map((entry) => (
                        <option key={entry.id} value={entry.name}>
                            {formatInventoryCondition(entry.name)}
                        </option>
                    ))}
                </AdminSelect>

                {!knownCondition && (
                    <p className="text-xs text-admin-text3 -mt-2">
                        This unit's current condition isn't in the catalog — pick one above, or add it under Settings → Inventory
                        catalog first.
                    </p>
                )}

                <AdminButton type="submit" isLoading={processing}>
                    Save condition
                </AdminButton>
            </form>
        </AdminModal>
    );
}
