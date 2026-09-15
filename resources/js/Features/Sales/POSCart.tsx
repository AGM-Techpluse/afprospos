import Icon from '../../Components/Icons/Icon';
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
    onChangeQuantity: (itemId: number, quantity: number) => void;
    disabled?: boolean;
}

/** Right pane of the POS Checkout screen (UI/UX §14C.3). Dark treatment — items/totals sit on a near-black panel. */
export default function POSCart({ items, subtotalMinor, discountMinor, totalMinor, onRemoveItem, onChangeQuantity, disabled = false }: POSCartProps) {
    return (
        <div className="flex flex-col h-full">
            {items.length === 0 ? (
                <div className="flex flex-col items-center justify-center text-center py-10 px-4">
                    <div className="w-10 h-10 rounded-admin-card bg-white/10 flex items-center justify-center text-white/50 text-lg mb-3">
                        <Icon name="cart" />
                    </div>
                    <h3 className="text-sm font-semibold text-white">Cart is empty</h3>
                    <p className="text-white/50 text-xs mt-1 max-w-xs">Search or scan a product to begin.</p>
                </div>
            ) : (
                <ul className="flex-1 flex flex-col gap-2 overflow-y-auto mb-4">
                    {items.map((item) => {
                        const isSerialized = item.inventory_item_id !== null;

                        return (
                            <li key={item.id} className="flex items-center justify-between gap-2 border-b border-white/10 pb-2">
                                <div className="min-w-0">
                                    <div className="text-sm text-white truncate">{item.product_name ?? item.sku_code ?? `SKU #${item.sku_id}`}</div>
                                    <div className="text-xs text-white/50 flex items-center gap-1.5 mt-0.5">
                                        {isSerialized ? (
                                            <>
                                                {formatNaira(item.unit_price_minor)}
                                                {' · '}
                                                <SerializedUnitPicker inventoryItemId={item.inventory_item_id as number} />
                                            </>
                                        ) : (
                                            <>
                                                <button
                                                    type="button"
                                                    disabled={disabled || item.quantity <= 1}
                                                    onClick={() => onChangeQuantity(item.id, item.quantity - 1)}
                                                    aria-label="Decrease quantity"
                                                    className="w-5 h-5 rounded-full bg-white/10 text-white flex items-center justify-center hover:bg-white/20 disabled:opacity-40 disabled:cursor-not-allowed"
                                                >
                                                    <Icon name="dash" />
                                                </button>
                                                <span className="text-white font-mono w-5 text-center">{item.quantity}</span>
                                                <button
                                                    type="button"
                                                    disabled={disabled}
                                                    onClick={() => onChangeQuantity(item.id, item.quantity + 1)}
                                                    aria-label="Increase quantity"
                                                    className="w-5 h-5 rounded-full bg-white/10 text-white flex items-center justify-center hover:bg-white/20 disabled:opacity-40 disabled:cursor-not-allowed"
                                                >
                                                    <Icon name="plus-lg" />
                                                </button>
                                                <span className="text-white/50">× {formatNaira(item.unit_price_minor)}</span>
                                            </>
                                        )}
                                    </div>
                                </div>
                                <div className="flex items-center gap-3 shrink-0">
                                    <span className="text-sm font-mono text-white">{formatNaira(item.unit_price_minor * item.quantity)}</span>
                                    <button
                                        type="button"
                                        disabled={disabled}
                                        onClick={() => onRemoveItem(item.id)}
                                        aria-label="Remove item"
                                        className="text-white/40 hover:text-admin-red disabled:cursor-not-allowed"
                                    >
                                        <Icon name="x-lg" />
                                    </button>
                                </div>
                            </li>
                        );
                    })}
                </ul>
            )}

            <CheckoutTotals subtotalMinor={subtotalMinor} discountMinor={discountMinor} totalMinor={totalMinor} />
        </div>
    );
}
