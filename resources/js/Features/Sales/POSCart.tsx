import Icon from '../../Components/Icons/Icon';
import AdminEmptyState from '../../Components/Admin/AdminEmptyState';
import CheckoutTotals from './CheckoutTotals';
import SerializedUnitPicker from './SerializedUnitPicker';
import { formatNaira } from '../../lib/money';

export type CheckoutItemView = {
    id: number;
    sku_id: number;
    sku_code: string | null;
    product_name: string | null;
    inventory_item_id: number | null;
    quantity: number;
    unit_price_minor: number;
};

interface POSCartProps {
    items: CheckoutItemView[];
    subtotalMinor: number;
    discountMinor: number;
    totalMinor: number;
    onRemoveItem: (itemId: number) => void;
    disabled?: boolean;
}

/** Right pane of the POS Checkout screen (UI/UX §14C.3). */
export default function POSCart({ items, subtotalMinor, discountMinor, totalMinor, onRemoveItem, disabled = false }: POSCartProps) {
    return (
        <div className="flex flex-col h-full">
            {items.length === 0 ? (
                <AdminEmptyState icon="cart" title="Cart is empty" description="Search or scan a product to begin." />
            ) : (
                <ul className="flex-1 flex flex-col gap-2 overflow-y-auto mb-4">
                    {items.map((item) => (
                        <li key={item.id} className="flex items-center justify-between gap-2 border-b border-admin-border pb-2">
                            <div>
                                <div className="text-sm text-admin-text">{item.product_name ?? item.sku_code ?? `SKU #${item.sku_id}`}</div>
                                <div className="text-xs text-admin-text3">
                                    {item.quantity} × {formatNaira(item.unit_price_minor)}
                                    {item.inventory_item_id && (
                                        <>
                                            {' · '}
                                            <SerializedUnitPicker inventoryItemId={item.inventory_item_id} />
                                        </>
                                    )}
                                </div>
                            </div>
                            <div className="flex items-center gap-3 shrink-0">
                                <span className="text-sm font-mono text-admin-text">{formatNaira(item.unit_price_minor * item.quantity)}</span>
                                <button
                                    type="button"
                                    disabled={disabled}
                                    onClick={() => onRemoveItem(item.id)}
                                    aria-label="Remove item"
                                    className="text-admin-text3 hover:text-admin-red disabled:cursor-not-allowed"
                                >
                                    <Icon name="x-lg" />
                                </button>
                            </div>
                        </li>
                    ))}
                </ul>
            )}

            <CheckoutTotals subtotalMinor={subtotalMinor} discountMinor={discountMinor} totalMinor={totalMinor} />
        </div>
    );
}
