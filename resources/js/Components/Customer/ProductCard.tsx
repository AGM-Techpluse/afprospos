import { Link, usePage } from '@inertiajs/react';
import ProductImage from '../ProductImage';
import Icon from '../Icons/Icon';
import { formatNaira } from '../../lib/money';
import { CartSummary } from '../../types/cart';

export type ShopAvailability = { shop_id: number; shop_name: string; available: number };

export type ProductCardData = {
    sku_id: number;
    brand: string;
    model: string;
    category: string;
    image_url: string | null;
    selling_price_minor: number;
    available_total: number;
    shops_available: ShopAvailability[];
};

interface ProductCardProps {
    product: ProductCardData;
    isLoggedIn: boolean;
    onQuickAdd: (product: ProductCardData) => void;
}

/** The storefront's one product-card shape — reused by the search grid, the "Featured"/category rows on the Shop homepage, and "More like this" on the product detail page, so a card looks and behaves identically everywhere it appears. */
export default function ProductCard({ product, isLoggedIn, onQuickAdd }: ProductCardProps) {
    const { cart } = usePage().props as { cart?: CartSummary | null };
    const outOfStock = product.available_total < 1;
    const lowStock = !outOfStock && product.available_total <= 3;
    // A self-service cart is shop-scoped — once it holds something, adding
    // a product that's only stocked elsewhere would just fail. Shown up
    // front so that's not a surprise after clicking (same check the
    // parent page's onQuickAdd also makes before actually sending the request).
    const lockedElsewhere =
        !outOfStock && cart != null && !product.shops_available.some((shop) => shop.shop_id === cart.shop_id);

    return (
        <div className="group bg-white rounded-customer-card overflow-hidden flex flex-col h-full border border-customer-divider transition-shadow duration-200 hover:shadow-customer-soft">
            <Link href={`/shop/${product.sku_id}`} className="block relative bg-customer-page-end">
                <div className="p-3">
                    <ProductImage
                        src={product.image_url}
                        alt={`${product.brand} ${product.model}`}
                        aspect="square"
                        category={product.category}
                        className="rounded-customer-item transition-transform duration-200 group-hover:scale-[1.03]"
                    />
                </div>
                {outOfStock && (
                    <span className="absolute top-2 left-2 text-[10px] font-bold uppercase tracking-wide bg-customer-red text-white px-2 py-1 rounded-customer-pill">
                        Out of stock
                    </span>
                )}
            </Link>

            <div className="px-3 pb-3 pt-2.5 border-t border-customer-divider flex flex-col gap-1 flex-1">
                <span className="text-[10px] font-bold uppercase tracking-wide text-customer-text2">{product.brand}</span>
                <Link
                    href={`/shop/${product.sku_id}`}
                    className="font-customer-display text-customer-caption font-bold text-customer-text leading-snug line-clamp-2 min-h-[2.3em]"
                >
                    {product.model}
                </Link>

                <div className="mt-0.5 flex items-center justify-between gap-2">
                    <span className="font-customer-display text-base font-extrabold text-customer-text">
                        {formatNaira(product.selling_price_minor)}
                    </span>
                    {lockedElsewhere ? (
                        <span className="text-[10px] font-bold text-customer-yellow-text">Other shop</span>
                    ) : (
                        lowStock && <span className="text-[10px] font-bold text-customer-red">{product.available_total} left</span>
                    )}
                </div>

                <div className="mt-auto pt-2.5 flex items-center gap-2">
                    <Link
                        href={`/shop/${product.sku_id}`}
                        className="flex-1 text-center rounded-customer-pill border border-customer-blue text-customer-blue text-[11px] font-bold py-2 transition-colors hover:bg-customer-blue hover:text-white"
                    >
                        View
                    </Link>
                    <button
                        type="button"
                        disabled={outOfStock}
                        onClick={() => onQuickAdd(product)}
                        aria-label={lockedElsewhere ? 'Not available at your cart’s shop' : 'Add to cart'}
                        title={lockedElsewhere ? `Your cart is reserved at ${cart!.shop_name ?? 'another shop'}` : undefined}
                        className={`w-9 h-9 rounded-full flex items-center justify-center shrink-0 transition-all duration-150 hover:scale-110 disabled:opacity-40 disabled:hover:scale-100 ${
                            lockedElsewhere
                                ? 'bg-customer-yellow-soft text-customer-yellow-text hover:bg-customer-yellow'
                                : 'bg-customer-blue text-white hover:bg-customer-blue-hover disabled:bg-customer-divider disabled:text-customer-text2 disabled:hover:bg-customer-divider'
                        }`}
                    >
                        <Icon name={lockedElsewhere ? 'shop' : 'cart-plus'} />
                    </button>
                </div>
            </div>
        </div>
    );
}
