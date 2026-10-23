import { Link, router, usePage } from '@inertiajs/react';
import Icon from '../Icons/Icon';
import ProductImage from '../ProductImage';
import CustomerButton from './CustomerButton';
import CustomerEmptyState from './CustomerEmptyState';
import { formatNaira } from '../../lib/money';
import { useToast } from '../../hooks/useToast';

type CartItem = {
    id: number;
    sku_id: number;
    product_name: string | null;
    variant_label: string | null;
    image_url: string | null;
    quantity: number;
    unit_price_minor: number;
};

type Cart = {
    id: number;
    item_count: number;
    subtotal_minor: number;
    total_minor: number;
    reservation_expires_at: string;
    items: CartItem[];
};

interface ShopAsideProps {
    isOpen: boolean;
    onClose: () => void;
}

/**
 * Logged-in-customer-only cart panel for the Shop pages (rendered by
 * PublicStorefrontShell) — reserved items, pinned to the left at every
 * screen size (a sticky sidebar on desktop, a hamburger-triggered
 * drawer on mobile). Search/category live in the page's own main
 * content instead (`ShopSearchBar`, used directly by `Shop/Index.tsx`/
 * `Shop/Show.tsx`) — squeezing them in here, on top of the cart, was
 * the same "everything crammed into a narrow column" problem twice over.
 */
export default function ShopAside({ isOpen, onClose }: ShopAsideProps) {
    const { cart } = usePage().props as { cart?: Cart | null };
    const { toast } = useToast();

    function updateQuantity(itemId: number, quantity: number) {
        if (quantity < 1) return;
        router.patch(`/customer/cart/items/${itemId}`, { quantity }, {
            preserveScroll: true,
            only: ['cart'],
            onError: (errors) => toast({ kind: 'danger', title: 'Could not update quantity', message: Object.values(errors)[0] as string }),
        });
    }

    function removeItem(itemId: number) {
        router.delete(`/customer/cart/items/${itemId}`, {
            preserveScroll: true,
            only: ['cart'],
            onError: (errors) => toast({ kind: 'danger', title: 'Could not remove item', message: Object.values(errors)[0] as string }),
        });
    }

    return (
        <>
            {isOpen && (
                <div className="sm:hidden fixed inset-0 z-40 bg-customer-overlay" onClick={onClose} aria-hidden="true" />
            )}

            <aside
                className={`
                    print:hidden
                    fixed inset-y-0 left-0 sm:sticky sm:top-16 sm:inset-auto sm:self-start
                    w-customer-drawer sm:w-customer-sidebar
                    sm:max-h-[calc(100vh-4rem)]
                    bg-white border-r border-gray-100
                    rounded-r-customer-drawer sm:rounded-none
                    z-50 sm:z-auto
                    flex flex-col
                    transition-transform duration-220
                    ${isOpen ? 'translate-x-0 shadow-customer-strong' : '-translate-x-full sm:translate-x-0'}
                `}
            >
                <div className="px-4 pt-8 pb-4 shrink-0">
                    <p className="text-customer-caption font-bold text-customer-text flex items-center justify-between">
                        Your reserved items
                        {cart !== undefined && cart !== null && cart.item_count > 0 && (
                            <span className="text-[11px] font-bold text-customer-blue bg-customer-blue-soft rounded-customer-pill px-2 py-0.5">
                                {cart.item_count}
                            </span>
                        )}
                    </p>
                </div>

                <div className="border-b border-gray-100" />

                <div className="flex-1 overflow-y-auto px-4 pt-4 pb-8 flex flex-col">
                    <div>
                        {!cart || cart.items.length === 0 ? (
                            <CustomerEmptyState icon="bag" description="Nothing reserved yet — add a product to hold it for you." />
                        ) : (
                            <>
                                <ul className="flex flex-col gap-3">
                                    {cart.items.map((item) => (
                                        <li key={item.id} className="flex gap-2">
                                            <ProductImage src={item.image_url} alt={item.product_name ?? ''} aspect="square" className="w-12 shrink-0" />
                                            <div className="flex-1 min-w-0">
                                                <p className="text-[12px] font-bold text-customer-text truncate">{item.product_name}</p>
                                                {item.variant_label && (
                                                    <p className="text-[11px] text-customer-text2 truncate">{item.variant_label}</p>
                                                )}
                                                <div className="flex items-center gap-2 mt-1">
                                                    <button
                                                        type="button"
                                                        onClick={() => updateQuantity(item.id, item.quantity - 1)}
                                                        className="w-5 h-5 rounded-full bg-customer-divider text-customer-text2 flex items-center justify-center text-xs transition-all duration-150 hover:scale-110 hover:bg-gray-300"
                                                        aria-label="Decrease quantity"
                                                    >
                                                        <Icon name="dash" />
                                                    </button>
                                                    <span className="text-[12px] font-bold text-customer-text w-4 text-center">{item.quantity}</span>
                                                    <button
                                                        type="button"
                                                        onClick={() => updateQuantity(item.id, item.quantity + 1)}
                                                        className="w-5 h-5 rounded-full bg-customer-divider text-customer-text2 flex items-center justify-center text-xs transition-all duration-150 hover:scale-110 hover:bg-gray-300"
                                                        aria-label="Increase quantity"
                                                    >
                                                        <Icon name="plus" />
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => removeItem(item.id)}
                                                        className="ml-auto text-customer-text2 transition-colors hover:text-customer-red"
                                                        aria-label="Remove from cart"
                                                    >
                                                        <Icon name="trash" />
                                                    </button>
                                                </div>
                                            </div>
                                        </li>
                                    ))}
                                </ul>

                                <div className="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between text-customer-caption">
                                    <span className="text-customer-text2">Subtotal</span>
                                    <span className="font-bold text-customer-text">{formatNaira(cart.subtotal_minor)}</span>
                                </div>
                                <p className="text-[11px] text-customer-text2 mt-1">
                                    Reserved until{' '}
                                    {new Date(cart.reservation_expires_at).toLocaleString('en-NG', { dateStyle: 'medium', timeStyle: 'short' })}
                                </p>

                                <Link href={`/customer/payments/checkouts/${cart.id}`} className="block mt-3">
                                    <CustomerButton type="button" className="w-full">Pay by bank transfer</CustomerButton>
                                </Link>
                                <p className="text-[11px] text-customer-text2 text-center mt-2">
                                    Or visit any of our shops to pay and collect in person.
                                </p>
                            </>
                        )}
                    </div>
                </div>
            </aside>
        </>
    );
}
