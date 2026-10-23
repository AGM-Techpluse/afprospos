import { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import PublicStorefrontShell from '../../Components/Customer/PublicStorefrontShell';
import CustomerButton from '../../Components/Customer/CustomerButton';
import CustomerSelect from '../../Components/Customer/Forms/CustomerSelect';
import ProductImage from '../../Components/ProductImage';
import ProductCard, { ProductCardData } from '../../Components/Customer/ProductCard';
import Icon from '../../Components/Icons/Icon';
import { formatNaira } from '../../lib/money';
import { useToast } from '../../hooks/useToast';
import { CartSummary } from '../../types/cart';

type ShopAvailability = { shop_id: number; shop_name: string; available: number };

type Sibling = {
    sku_id: number;
    sku_code: string;
    attributes: Record<string, unknown>;
    selling_price_minor: number;
    available_total: number;
};

type Product = {
    sku_id: number;
    sku_code: string;
    brand: string;
    model: string;
    category: string;
    description: string | null;
    image_url: string | null;
    attributes: Record<string, unknown>;
    is_serialized: boolean;
    selling_price_minor: number;
    available_total: number;
    shops_available: ShopAvailability[];
    siblings: Sibling[];
};

const TRUST_POINTS: { icon: string; text: string }[] = [
    { icon: 'lightning-charge', text: 'Reserve instantly' },
    { icon: 'bank', text: 'Pay by transfer or at pickup' },
    { icon: 'shop', text: 'Available across shops' },
];

function attributeLabel(attributes: Record<string, unknown>): string {
    const parts = ['color', 'storage', 'material', 'compatibility']
        .map((key) => attributes[key])
        .filter((value): value is string => typeof value === 'string' && value !== '');

    return parts.join(' · ');
}

interface ShopShowProps {
    product: Product;
    relatedProducts: ProductCardData[];
}

export default function ShopShow({ product, relatedProducts }: ShopShowProps) {
    const variant = attributeLabel(product.attributes);
    const { auth, cart } = usePage().props as unknown as { auth: any; cart?: CartSummary | null };
    const isLoggedIn = Boolean(auth?.customer);
    const { toast } = useToast();
    const inStock = product.available_total >= 1;
    const pickupShops = product.shops_available.filter((shop) => shop.available > 0);
    // A self-service cart is shop-scoped — once it holds something, this
    // product can only join it if it's also stocked at that same shop.
    const cartLockedElsewhere = cart != null && !pickupShops.some((shop) => shop.shop_id === cart.shop_id);
    const preferredShopId = cart != null ? pickupShops.find((shop) => shop.shop_id === cart.shop_id)?.shop_id : undefined;
    const [shopId, setShopId] = useState<string>((preferredShopId ?? pickupShops[0]?.shop_id)?.toString() ?? '');
    const [quantity, setQuantity] = useState(1);
    const [isAdding, setIsAdding] = useState(false);

    function addToCart() {
        setIsAdding(true);
        router.post(
            '/customer/cart/items',
            { sku_id: product.sku_id, shop_id: Number(shopId), quantity },
            {
                preserveScroll: true,
                only: ['cart', 'errors'],
                onFinish: () => setIsAdding(false),
                onSuccess: () => toast({ kind: 'success', title: 'Added to your cart' }),
                onError: (errors) => toast({ kind: 'danger', title: 'Could not add to cart', message: Object.values(errors)[0] as string }),
            },
        );
    }

    function quickAdd(related: ProductCardData) {
        if (!isLoggedIn) {
            router.visit('/customer/login');

            return;
        }

        const preferredShop = cart != null ? related.shops_available.find((s) => s.shop_id === cart.shop_id) : undefined;
        const shop = preferredShop ?? related.shops_available[0];
        if (!shop) return;

        if (cart != null && shop.shop_id !== cart.shop_id) {
            toast({
                kind: 'warning',
                title: 'Different pickup shop',
                message: `Your cart is reserved at ${cart.shop_name ?? 'another shop'}. Clear it first to shop from ${shop.shop_name}.`,
            });

            return;
        }

        router.post(
            '/customer/cart/items',
            { sku_id: related.sku_id, shop_id: shop.shop_id, quantity: 1 },
            {
                preserveScroll: true,
                preserveState: true,
                only: ['cart'],
                onSuccess: () => toast({ kind: 'success', title: 'Added to your cart' }),
                onError: (errors) => toast({ kind: 'danger', title: 'Could not add to cart', message: Object.values(errors)[0] as string }),
            },
        );
    }

    return (
        <PublicStorefrontShell>
            <Head title={`${product.brand} ${product.model}`} />

            <nav className="flex items-center gap-1.5 text-[12px] text-customer-text2 mb-5 overflow-hidden">
                <Link href="/shop" className="font-semibold transition-colors hover:text-customer-blue shrink-0">Shop</Link>
                <Icon name="chevron-right" className="text-[9px] shrink-0" />
                <Link
                    href={`/shop?category=${encodeURIComponent(product.category)}`}
                    className="font-semibold transition-colors hover:text-customer-blue shrink-0"
                >
                    {product.category}
                </Link>
                <Icon name="chevron-right" className="text-[9px] shrink-0" />
                <span className="text-customer-text font-semibold truncate">{product.brand} {product.model}</span>
            </nav>

            <div className="grid gap-8 sm:grid-cols-2">
                <div className="bg-gray-50 rounded-customer-hero p-6 sm:p-10 h-fit">
                    <ProductImage
                        src={product.image_url}
                        alt={`${product.brand} ${product.model}`}
                        aspect="wide"
                        category={product.category}
                        className="rounded-customer-card"
                    />
                </div>

                <div>
                    <span className="inline-block rounded-customer-pill bg-customer-blue-soft text-customer-blue px-3 py-1 text-[11px] font-bold uppercase tracking-wide">
                        {product.category}
                    </span>
                    <h1 className="font-customer-display font-extrabold text-2xl text-customer-text mt-2">
                        {product.brand} {product.model}
                    </h1>
                    {variant && <p className="text-customer-caption text-customer-text2 mt-1">{variant}</p>}

                    <p className="font-customer-display font-extrabold text-2xl text-customer-text mt-4">
                        {formatNaira(product.selling_price_minor)}
                    </p>

                    {product.siblings.length > 0 && (
                        <div className="mt-5">
                            <p className="text-[11px] font-bold text-customer-text2 uppercase tracking-wide mb-2">Options</p>
                            <div className="flex flex-wrap gap-2">
                                <span className="rounded-xl border-2 border-customer-blue bg-customer-blue-soft px-3 py-2 text-[12px] font-bold text-customer-blue">
                                    {variant || product.sku_code}
                                </span>
                                {product.siblings.map((sibling) => {
                                    const siblingOutOfStock = sibling.available_total < 1;

                                    return (
                                        <Link
                                            key={sibling.sku_id}
                                            href={`/shop/${sibling.sku_id}`}
                                            className={`rounded-xl border px-3 py-2 text-[12px] font-bold transition-colors ${
                                                siblingOutOfStock
                                                    ? 'border-gray-200 text-customer-text2 opacity-50'
                                                    : 'border-gray-200 text-customer-text hover:border-customer-blue hover:text-customer-blue'
                                            }`}
                                        >
                                            {attributeLabel(sibling.attributes) || sibling.sku_code}
                                        </Link>
                                    );
                                })}
                            </div>
                        </div>
                    )}

                    <div className="mt-5">
                        <p className="text-[11px] font-bold text-customer-text2 uppercase tracking-wide mb-2">Available for pickup</p>
                        {product.shops_available.length === 0 ? (
                            <p className="text-customer-caption text-customer-red">Currently out of stock at every shop.</p>
                        ) : (
                            <div className="flex flex-wrap gap-2">
                                {product.shops_available.map((shop) => (
                                    <span
                                        key={shop.shop_id}
                                        className="flex items-center gap-1.5 rounded-xl border border-gray-200 px-3 py-2 text-[12px]"
                                    >
                                        <span className="w-1.5 h-1.5 rounded-full bg-customer-green shrink-0" />
                                        <span className="font-semibold text-customer-text">{shop.shop_name}</span>
                                        <span className="text-customer-text2">· {shop.available} left</span>
                                    </span>
                                ))}
                            </div>
                        )}
                    </div>

                    {product.description && (
                        <div className="mt-5">
                            <p className="text-[11px] font-bold text-customer-text2 uppercase tracking-wide mb-2">Description</p>
                            <p className="text-customer-caption text-customer-text2 whitespace-pre-wrap">{product.description}</p>
                        </div>
                    )}

                    <div className="mt-6 bg-white rounded-customer-card border border-gray-100 p-4">
                        {!inStock ? (
                            <CustomerButton type="button" disabled className="w-full">Out of stock</CustomerButton>
                        ) : !isLoggedIn ? (
                            <>
                                <Link href="/customer/login">
                                    <CustomerButton type="button" className="w-full">Log in to buy</CustomerButton>
                                </Link>
                                <p className="text-[11px] text-customer-text2 text-center mt-2">
                                    New here?{' '}
                                    <Link href="/customer/register" className="font-bold text-customer-blue hover:underline">
                                        Create an account
                                    </Link>
                                </p>
                            </>
                        ) : cartLockedElsewhere ? (
                            <>
                                <p className="text-customer-caption text-customer-text2">
                                    Your cart is reserved for pickup at <span className="font-bold text-customer-text">{cart!.shop_name}</span>.
                                    This item isn't available there.
                                </p>
                                <CustomerButton
                                    type="button"
                                    variant="neutral"
                                    className="w-full mt-3"
                                    onClick={() =>
                                        router.post(
                                            '/customer/cart/cancel',
                                            {},
                                            {
                                                preserveScroll: true,
                                                only: ['cart'],
                                                onSuccess: () => toast({ kind: 'success', title: 'Cart cleared' }),
                                            },
                                        )
                                    }
                                >
                                    Clear your cart to shop here instead
                                </CustomerButton>
                            </>
                        ) : (
                            <>
                                <CustomerSelect label="Pick up from" value={shopId} onChange={(e) => setShopId(e.target.value)}>
                                    {pickupShops.map((shop) => (
                                        <option key={shop.shop_id} value={shop.shop_id}>
                                            {shop.shop_name} ({shop.available} available)
                                        </option>
                                    ))}
                                </CustomerSelect>

                                {!product.is_serialized && (
                                    <div className="flex items-center gap-3 mb-6 -mt-4">
                                        <button
                                            type="button"
                                            onClick={() => setQuantity((q) => Math.max(1, q - 1))}
                                            className="w-8 h-8 rounded-full bg-customer-divider text-customer-text2 flex items-center justify-center transition-all duration-150 hover:scale-110 hover:bg-gray-300"
                                            aria-label="Decrease quantity"
                                        >
                                            <Icon name="dash" />
                                        </button>
                                        <span className="font-bold text-customer-text w-6 text-center">{quantity}</span>
                                        <button
                                            type="button"
                                            onClick={() => setQuantity((q) => q + 1)}
                                            className="w-8 h-8 rounded-full bg-customer-divider text-customer-text2 flex items-center justify-center transition-all duration-150 hover:scale-110 hover:bg-gray-300"
                                            aria-label="Increase quantity"
                                        >
                                            <Icon name="plus" />
                                        </button>
                                    </div>
                                )}

                                <CustomerButton
                                    type="button"
                                    className="w-full"
                                    isLoading={isAdding}
                                    disabled={shopId === ''}
                                    onClick={addToCart}
                                >
                                    Reserve / add to cart
                                </CustomerButton>
                                <p className="text-[11px] text-customer-text2 text-center mt-2">
                                    No payment now — pay by bank transfer or when you collect in person.
                                </p>
                            </>
                        )}
                    </div>

                    <div className="mt-4 grid grid-cols-3 gap-2">
                        {TRUST_POINTS.map((point) => (
                            <div key={point.text} className="flex flex-col items-center text-center gap-1.5 p-3 rounded-xl bg-gray-50">
                                <Icon name={point.icon} className="text-customer-blue text-lg" />
                                <span className="text-[10.5px] font-semibold text-customer-text2 leading-tight">{point.text}</span>
                            </div>
                        ))}
                    </div>
                </div>
            </div>

            {relatedProducts.length > 0 && (
                <section className="mt-12">
                    <h2 className="font-customer-display font-extrabold text-lg text-customer-text mb-3">More like this</h2>
                    <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        {relatedProducts.map((related) => (
                            <ProductCard key={related.sku_id} product={related} isLoggedIn={isLoggedIn} onQuickAdd={quickAdd} />
                        ))}
                    </div>
                </section>
            )}
        </PublicStorefrontShell>
    );
}
