import Icon from '../../Components/Icons/Icon';

/**
 * Displays the specific unit the reservation engine auto-assigned to a
 * serialized cart line (Phase 4 plan's resolved ambiguity #6 — not a
 * live manual picker; Inventory's reservation contract auto-selects
 * from available candidates and this confirms which one it chose).
 */
export default function SerializedUnitPicker({ inventoryItemId }: { inventoryItemId: number }) {
    return (
        <span className="inline-flex items-center gap-1 text-xs text-admin-text3">
            <Icon name="upc-scan" />
            Unit #{inventoryItemId}
        </span>
    );
}
