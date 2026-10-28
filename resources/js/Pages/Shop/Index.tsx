import { useEffect, useRef, useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import PublicStorefrontShell from '../../Components/Customer/PublicStorefrontShell';
import CustomerEmptyState from '../../Components/Customer/CustomerEmptyState';
import ShopSearchBar from '../../Components/Customer/ShopSearchBar';
import ProductCard, { ProductCardData } from '../../Components/Customer/ProductCard';
import { placeholderIcon } from '../../Components/ProductImage';
import Skeleton from '../../Components/Feedback/Skeleton';
import Icon from '../../Components/Icons/Icon';
import { useToast } from '../../hooks/useToast';
import { CartSummary } from '../../types/cart';

type ProductRow = ProductCardData;

type Paginated<T> = { data: T[]; current_page: number; last_page: number; per_page: number; total: number };

type BrowseSection = { category: string; products: ProductRow[] };

interface ShopIndexProps {
    products: Paginated<ProductRow> | null;
    browse: { featured: ProductRow[]; sections: BrowseSection[] } | null;
    filters: { search: string; category: string };
    categories: string[];
}

export default function ShopIndex({ products, browse, filters, categories }: ShopIndexProps) {
    const { auth, cart } = usePage().props as unknown as { auth: any; cart?: CartSummary | null };
    const isLoggedIn = Boolean(auth?.customer);
    const { toast } = useToast();
    const [search, setSearch] = useState(filters.search);
    const [category, setCategory] = useState(filters.category);
    const [loading, setLoading] = useState(false);
    const skipNextFetch = useRef(true);

    // Drives the top search bar — the one search control for everyone,
    // guest or logged-in, at every screen size (the aside next to it is
    // cart-only now).
    useEffect(() => {
        if (skipNextFetch.current) {
            skipNextFetch.current = false;
            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                '/shop',
                { search: search || undefined, category: category || undefined },
                {
                    only: ['products', 'filters', 'browse'],
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    showProgress: false,
                    onStart: () => setLoading(true),
                    onFinish: () => setLoading(false),
                },
            );
        }, 350);

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search, category]);

    // No shop-picker on a card — prefers the shop the customer's cart is
    // already reserved at (if this product is available there), else
    // falls back to its first available shop. A guest's "Add to cart"
    // sends them to log in first instead of failing silently; a mismatch
    // with an existing cart is caught here too, before any request goes
    // out, instead of surfacing only after a failed add.
    function quickAdd(product: ProductRow) {
        if (!isLoggedIn) {
            router.visit('/customer/login');

            return;
        }

        const preferredShop = cart != null ? product.shops_available.find((s) => s.shop_id === cart.shop_id) : undefined;
        const shop = preferredShop ?? product.shops_available[0];
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
            { sku_id: product.sku_id, shop_id: shop.shop_id, quantity: 1 },
            {
                preserveScroll: true,
                preserveState: true,
                only: ['cart'],
                onSuccess: () => toast({ kind: 'success', title: 'Added to your cart' }),
                onError: (errors) => toast({ kind: 'danger', title: 'Could not add to cart', message: Object.values(errors)[0] as string }),
            },
        );
    }

    function goToPage(page: number) {
        router.get(
            '/shop',
            { search: filters.search || undefined, category: filters.category || undefined, page },
            {
                only: ['products'],
                preserveState: true,
                preserveScroll: true,
                replace: true,
                showProgress: false,
                onStart: () => setLoading(true),
                onFinish: () => setLoading(false),
            },
        );
    }

    return (
        <PublicStorefrontShell>
            <Head title="Shop" />

            {!isLoggedIn && (
                <>
                    <h1 className="font-customer-display font-bold text-2xl text-customer-text mb-1">Shop devices</h1>
                    <p className="text-customer-text2 text-customer-caption mb-4">Browse what's in stock across every shop.</p>
                </>
            )}

            {/* Sticky, not just top-of-page — pinned under the header so search/filter stay reachable while scrolling the grid instead of needing a trip back to the top. */}
            <div className="sticky top-16 z-20 bg-white -mx-4 sm:-mx-8 px-4 sm:px-8 py-3 mb-2 border-b border-gray-100">
                <ShopSearchBar
                    search={search}
                    onSearchChange={setSearch}
                    category={category}
                    onCategoryChange={setCategory}
                    categories={categories}
                />
            </div>

            {loading ? (
                <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                    {Array.from({ length: 8 }).map((_, i) => (
                        <div key={i} className="bg-white rounded-customer-card overflow-hidden">
                            <Skeleton className="aspect-square w-full rounded-none" />
                            <div className="p-3 flex flex-col gap-2">
                                <Skeleton className="h-3.5 w-3/4" />
                                <Skeleton className="h-3.5 w-1/3" />
                            </div>
                        </div>
                    ))}
                </div>
            ) : products !== null ? (
                // Search/filter mode — a flat, paginated results grid.
                <>
                    <h1 className="font-customer-display font-bold text-xl text-customer-text mb-4">
                        {products.total} {products.total === 1 ? 'result' : 'results'}
                    </h1>

                    {products.data.length === 0 ? (
                        <CustomerEmptyState icon="bag" description="No products match your search." />
                    ) : (
                        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                            {products.data.map((product) => (
                                <ProductCard key={product.sku_id} product={product} isLoggedIn={isLoggedIn} onQuickAdd={quickAdd} />
                            ))}
                        </div>
                    )}

                    {products.last_page > 1 && (
                        <div className="flex items-center justify-center gap-2 mt-8">
                            <button
                                type="button"
                                disabled={products.current_page <= 1}
                                onClick={() => goToPage(products.current_page - 1)}
                                className="w-9 h-9 rounded-customer-pill bg-white shadow-customer-soft flex items-center justify-center text-customer-text2 transition-all duration-150 hover:scale-110 hover:bg-gray-50 hover:text-customer-text disabled:opacity-40 disabled:hover:scale-100 disabled:hover:bg-white disabled:hover:text-customer-text2"
                            >
                                <Icon name="chevron-left" />
                            </button>
                            <span className="text-customer-caption text-customer-text2">
                                Page {products.current_page} of {products.last_page}
                            </span>
                            <button
                                type="button"
                                disabled={products.current_page >= products.last_page}
                                onClick={() => goToPage(products.current_page + 1)}
                                className="w-9 h-9 rounded-customer-pill bg-white shadow-customer-soft flex items-center justify-center text-customer-text2 transition-all duration-150 hover:scale-110 hover:bg-gray-50 hover:text-customer-text disabled:opacity-40 disabled:hover:scale-100 disabled:hover:bg-white disabled:hover:text-customer-text2"
                            >
                                <Icon name="chevron-right" />
                            </button>
                        </div>
                    )}
                </>
            ) : browse !== null ? (
                // Browse mode — a compact hero, "Featured products", then one row per category.
                <>
                    <div className="bg-customer-hero-gradient rounded-customer-hero px-6 py-8 sm:px-10 sm:py-10 mb-8 text-white">
                        <p className="text-customer-caption font-bold uppercase tracking-wide opacity-80">AfProsPos Shop</p>
                        <h1 className="font-customer-display font-bold text-2xl sm:text-3xl mt-1">
                            Genuine devices, every shop, one place.
                        </h1>
                        <p className="text-customer-caption opacity-90 mt-2 max-w-md">
                            Reserve what you want online, then pay by bank transfer or collect and pay in person.
                        </p>
                        <a
                            href="#featured"
                            className="inline-flex items-center gap-1.5 mt-5 rounded-customer-pill bg-white text-customer-blue px-4 py-2 text-customer-caption font-bold transition-colors hover:bg-gray-100"
                        >
                            Shop now
                            <Icon name="arrow-down" />
                        </a>
                    </div>

                    {/* Category shortcuts — a quick jump to a filtered view, same idea as Jumia's circular category icons on its homepage. */}
                    {categories.length > 0 && (
                        <section className="mb-8">
                            <div className="flex gap-4 overflow-x-auto pb-1 -mx-4 px-4 sm:mx-0 sm:px-0 sm:flex-wrap">
                                {categories.map((name) => (
                                    <Link
                                        key={name}
                                        href={`/shop?category=${encodeURIComponent(name)}`}
                                        className="group flex flex-col items-center gap-2 shrink-0 w-20"
                                    >
                                        <span className="w-14 h-14 rounded-full bg-customer-blue-soft text-customer-blue flex items-center justify-center text-xl transition-all duration-150 group-hover:scale-110 group-hover:bg-customer-blue group-hover:text-white">
                                            <Icon name={placeholderIcon(name)} />
                                        </span>
                                        <span className="text-[11px] font-bold text-customer-text text-center truncate w-full">{name}</span>
                                    </Link>
                                ))}
                            </div>
                        </section>
                    )}

                    {browse.featured.length > 0 && (
                        <section id="featured" className="mb-8 scroll-mt-20">
                            <h2 className="font-customer-display font-bold text-lg text-customer-text mb-3">Featured products</h2>
                            <div className="flex gap-4 overflow-x-auto pb-2 -mx-4 px-4 sm:mx-0 sm:px-0 sm:grid sm:grid-cols-4 lg:grid-cols-4">
                                {browse.featured.map((product) => (
                                    <div key={product.sku_id} className="w-36 shrink-0 sm:w-auto">
                                        <ProductCard product={product} isLoggedIn={isLoggedIn} onQuickAdd={quickAdd} />
                                    </div>
                                ))}
                            </div>
                        </section>
                    )}

                    {browse.sections.map((section) => (
                        <section key={section.category} className="mb-8">
                            <div className="flex items-center justify-between mb-3">
                                <h2 className="font-customer-display font-bold text-lg text-customer-text">{section.category}</h2>
                                <Link
                                    href={`/shop?category=${encodeURIComponent(section.category)}`}
                                    className="text-customer-caption font-bold text-customer-blue transition-colors hover:opacity-80 hover:underline"
                                >
                                    See all
                                </Link>
                            </div>
                            <div className="flex gap-4 overflow-x-auto pb-2 -mx-4 px-4 sm:mx-0 sm:px-0 sm:grid sm:grid-cols-4 lg:grid-cols-4">
                                {section.products.map((product) => (
                                    <div key={product.sku_id} className="w-36 shrink-0 sm:w-auto">
                                        <ProductCard product={product} isLoggedIn={isLoggedIn} onQuickAdd={quickAdd} />
                                    </div>
                                ))}
                            </div>
                        </section>
                    ))}
                </>
            ) : null}
        </PublicStorefrontShell>
    );
}
