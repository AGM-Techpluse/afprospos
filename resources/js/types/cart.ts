export type CartItemSummary = {
    id: number;
    sku_id: number;
    product_name: string | null;
    variant_label: string | null;
    image_url: string | null;
    quantity: number;
    unit_price_minor: number;
};

/** The shared `cart` Inertia prop (HandleInertiaRequests) — a light summary, not the full checkout shape. `shop_id`/`shop_name` are what a product's own `shops_available` is checked against before offering "Add to cart", since a self-service cart is shop-scoped. */
export type CartSummary = {
    id: number;
    shop_id: number;
    shop_name: string | null;
    item_count: number;
    subtotal_minor: number;
    total_minor: number;
    reservation_expires_at: string;
    items: CartItemSummary[];
};
