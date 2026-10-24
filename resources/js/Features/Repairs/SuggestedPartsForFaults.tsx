import { useEffect, useRef, useState } from 'react';
import { router } from '@inertiajs/react';
import AdminButton from '../../Components/Admin/AdminButton';

type Suggestion = { sku_id: number; sku_code: string | null; product_name: string | null };

interface SuggestedPartsForFaultsProps {
    repairId: number;
    /** The faults currently picked in the "actually fixed" list — not yet saved anywhere (only persisted when "Complete repair" is submitted), so suggestions are resolved live off these ids rather than any saved repair state. */
    tagIds: number[];
    /** SKU ids already reserved on this repair (`repair.part_reservations`) — a suggestion already acted on drops off the list instead of offering "Reserve" again. */
    reservedSkuIds: number[];
}

/**
 * A small, additive panel next to the fault picker — not a replacement
 * for the full parts page (`Parts.tsx`/`PartsReservationPicker`), just a
 * quick one-click way to reserve a commonly-needed part the moment a
 * fault is picked, while the repair is still `in_progress` and parts can
 * still actually be reserved.
 */
export default function SuggestedPartsForFaults({ repairId, tagIds, reservedSkuIds }: SuggestedPartsForFaultsProps) {
    const [suggestions, setSuggestions] = useState<Suggestion[]>([]);
    const requestId = useRef(0);
    const tagIdsKey = tagIds.join(',');

    useEffect(() => {
        if (tagIds.length === 0) {
            setSuggestions([]);

            return;
        }

        const currentRequest = ++requestId.current;
        const timeout = setTimeout(() => {
            const query = tagIds.map((id) => `tag_ids[]=${id}`).join('&');

            fetch(`/admin/repairs/${repairId}/faults/suggested-parts?${query}`, { credentials: 'same-origin' })
                .then((response) => response.json())
                .then((json: { results: Suggestion[] }) => {
                    if (requestId.current === currentRequest) {
                        setSuggestions(json.results);
                    }
                })
                .catch(() => {
                    if (requestId.current === currentRequest) {
                        setSuggestions([]);
                    }
                });
        }, 300);

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [repairId, tagIdsKey]);

    function reserve(skuId: number) {
        router.post(`/admin/repairs/${repairId}/parts`, { sku_id: skuId, quantity: 1 }, { preserveScroll: true });
    }

    const reservedSet = new Set(reservedSkuIds);
    const pending = suggestions.filter((part) => !reservedSet.has(part.sku_id));

    if (pending.length === 0) {
        return null;
    }

    return (
        <div className="flex flex-col gap-1.5 rounded-admin-button border border-admin-border p-2">
            <span className="text-[11px] font-semibold text-admin-text">Suggested parts for these faults</span>
            <ul className="flex flex-col gap-1.5">
                {pending.map((part) => (
                    <li key={part.sku_id} className="flex items-center justify-between gap-2 bg-admin-blue-soft rounded-admin-card p-2">
                        <div className="min-w-0 flex-1">
                            <div className="text-xs font-medium text-admin-text truncate">{part.product_name ?? part.sku_code ?? `SKU #${part.sku_id}`}</div>
                            {part.sku_code && <div className="text-[10px] text-admin-text3">{part.sku_code}</div>}
                        </div>
                        <AdminButton type="button" variant="neutral" onClick={() => reserve(part.sku_id)}>
                            Reserve
                        </AdminButton>
                    </li>
                ))}
            </ul>
        </div>
    );
}
