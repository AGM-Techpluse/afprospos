import { FormEvent, useEffect, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { useConfirm } from '../../../hooks/useConfirm';
import AdminShell from '../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../Components/Admin/AdminPageHead';
import AdminButton from '../../../Components/Admin/AdminButton';
import AdminSelect from '../../../Components/Admin/Forms/AdminSelect';
import AdminStatCard from '../../../Components/Admin/AdminStatCard';
import AlertBanner from '../../../Components/Feedback/AlertBanner';
import AdminEmptyState from '../../../Components/Admin/AdminEmptyState';
import ProductSearch from '../../../Features/Sales/ProductSearch';
import CustomerSearch, { CustomerSearchResult } from '../../../Features/Sales/CustomerSearch';
import { formatNaira } from '../../../lib/money';
import POSCart, { CheckoutItemView } from '../../../Features/Sales/POSCart';
import CheckoutExpiryTimer from '../../../Features/Sales/CheckoutExpiryTimer';

type Shop = { id: number; name: string; sku_prefix_code: string };

type CheckoutView = {
    id: number;
    shop_id: number;
    customer_id: number | null;
    customer_name: string | null;
    status: string;
    reservation_expires_at: string;
    subtotal_minor: number;
    discount_minor: number;
    total_minor: number;
    items: CheckoutItemView[];
};

type RecentSale = {
    id: number;
    invoice_number: string;
    total_minor: number;
    payment_method: string;
    created_at: string;
};

type TodaysSales = {
    sales_count: number;
    total_minor: number;
};

type PendingPaymentTransaction = {
    id: number;
    status: string;
    method: string;
    amount_minor: number;
    provider_reference: string | null;
};

interface CheckoutPageProps {
    checkout: CheckoutView | null;
    pendingPaymentTransaction: PendingPaymentTransaction | null;
    shops: Shop[];
    checkoutReservationMinutes: number;
    recentSales: RecentSale[];
    todaysSales: TodaysSales | null;
}

const DISCOUNT_TYPES = [
    { value: 'promotion', label: 'Promotion' },
    { value: 'referral_voucher', label: 'Referral voucher' },
    { value: 'trade_in_credit', label: 'Trade-in credit' },
    { value: 'store_credit', label: 'Store credit' },
];

const PAYMENT_METHODS = [
    { value: 'cash', label: 'Cash' },
    { value: 'pos_terminal', label: 'POS terminal' },
    { value: 'bank_transfer', label: 'Bank transfer' },
    { value: 'in_app', label: 'In-app (registered customer)' },
];

function NewCheckoutForm({ shops }: { shops: Shop[] }) {
    const { data, setData, post, processing, errors } = useForm({
        shop_id: shops[0]?.id.toString() ?? '',
        customer_id: '',
    });
    const [customer, setCustomer] = useState<CustomerSearchResult | null>(null);

    function submit(e: FormEvent) {
        e.preventDefault();
        post('/admin/sales/checkout');
    }

    return (
        <form onSubmit={submit}>
            <AdminSelect label="Shop" value={data.shop_id} onChange={(e) => setData('shop_id', e.target.value)} error={errors.shop_id}>
                {shops.map((shop) => (
                    <option key={shop.id} value={shop.id}>
                        {shop.name}
                    </option>
                ))}
            </AdminSelect>
            <CustomerSearch
                value={customer}
                onChange={(selected) => {
                    setCustomer(selected);
                    setData('customer_id', selected ? selected.id.toString() : '');
                }}
                error={errors.customer_id}
            />
            <AdminButton type="submit" isLoading={processing}>
                Start checkout
            </AdminButton>
        </form>
    );
}

function RecentSales({ sales }: { sales: RecentSale[] }) {
    if (sales.length === 0) {
        return (
            <div className="bg-admin-surface border border-admin-border rounded-admin-card">
                <AdminEmptyState icon="receipt" title="No recent sales" description="Your completed sales will show up here." />
            </div>
        );
    }

    return (
        <ul className="bg-admin-surface border border-admin-border rounded-admin-card divide-y divide-admin-border">
            {sales.map((sale) => (
                <li key={sale.id} className="flex items-center justify-between gap-3 p-4">
                    <div className="min-w-0">
                        <div className="text-sm font-medium text-admin-text truncate">{sale.invoice_number}</div>
                        <div className="text-xs text-admin-text3">{new Date(sale.created_at).toLocaleString()}</div>
                    </div>
                    <div className="text-right shrink-0">
                        <div className="text-sm font-mono text-admin-text">{formatNaira(sale.total_minor)}</div>
                        <button
                            type="button"
                            onClick={() => router.visit(`/admin/sales/${sale.id}`)}
                            className="text-xs font-semibold text-admin-blue"
                        >
                            View
                        </button>
                    </div>
                </li>
            ))}
        </ul>
    );
}

export default function SalesCheckout({ checkout, pendingPaymentTransaction, shops, recentSales, todaysSales }: CheckoutPageProps) {
    const confirm = useConfirm();
    const [inlineError, setInlineError] = useState<{ skuId: number; message: string } | null>(null);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [isExpired, setIsExpired] = useState(false);
    const [discountOpen, setDiscountOpen] = useState(false);

    const discountForm = useForm({ type: 'promotion', source_id: '1', amount_naira: '' });
    const completeForm = useForm({
        payment_method: pendingPaymentTransaction ? pendingPaymentTransaction.method : 'cash',
        payment_reference: pendingPaymentTransaction?.provider_reference ?? '',
        existing_payment_transaction_id: pendingPaymentTransaction ? String(pendingPaymentTransaction.id) : '',
    });

    const itemCount = checkout?.items.length ?? 0;

    // Native browser-level unload (tab close, refresh, address-bar navigation, real back/forward) —
    // the browser shows its own generic text regardless of what we pass; we can't use our modal here.
    useEffect(() => {
        if (itemCount === 0) return;

        const handler = (event: BeforeUnloadEvent) => {
            event.preventDefault();
        };

        window.addEventListener('beforeunload', handler);
        return () => window.removeEventListener('beforeunload', handler);
    }, [itemCount]);

    // In-app navigation away (sidebar link, browser-integrated back/forward Inertia already intercepts) —
    // this page's own actions (add item, apply discount, complete, cancel) are POST/DELETE and are
    // deliberately left alone; only GET visits are ever "leaving the page."
    useEffect(() => {
        if (itemCount === 0) return;

        let allowNextVisit = false;

        return router.on('before', (event) => {
            if (event.detail.visit.method !== 'get' || allowNextVisit) {
                allowNextVisit = false;
                return;
            }

            event.preventDefault();

            confirm({
                kind: 'warning',
                title: 'Leave this checkout?',
                body: 'Your cart and reservation stay held — you can resume this checkout by opening Sales / POS again. Leaving now just navigates away from it.',
                confirmLabel: 'Leave',
                cancelLabel: 'Stay',
            }).then((confirmed) => {
                if (confirmed) {
                    allowNextVisit = true;
                    router.visit(event.detail.visit.url, event.detail.visit);
                }
            });
        });
    }, [itemCount, confirm]);

    if (checkout === null) {
        return (
            <AdminShell>
                <Head title="New sale" />
                <AdminPageHead title="New sale" description="Start a checkout to begin building a cart." />
                <div className="max-w-[1160px] mx-auto">
                    {todaysSales && (
                        <div className="grid grid-cols-2 gap-4 mb-6 max-w-xl">
                            <AdminStatCard label="Today's sales" value={todaysSales.sales_count.toString()} variant="blue" fillHeight />
                            <AdminStatCard label="Today's revenue" value={formatNaira(todaysSales.total_minor)} variant="blue" fillHeight />
                        </div>
                    )}
                    <div className="grid gap-8 lg:grid-cols-[minmax(0,1fr)_420px] items-start">
                        <div>
                            <h2 className="text-sm font-semibold text-admin-text mb-3">Your recent sales</h2>
                            <RecentSales sales={recentSales} />
                        </div>
                        <NewCheckoutForm shops={shops} />
                    </div>
                </div>
            </AdminShell>
        );
    }

    const shop = shops.find((s) => s.id === checkout.shop_id);
    const closed = isExpired || checkout.status !== 'open';
    const locked = isSubmitting || closed;

    function addItem(skuId: number) {
        setInlineError(null);
        setIsSubmitting(true);

        // A non-serialized SKU already on the cart is topped up in place
        // rather than added as a second line — a serialized SKU always
        // reserves one specific physical unit, so a second click there
        // correctly starts a new line (a different unit).
        const existing = checkout!.items.find((item) => item.sku_id === skuId && item.inventory_item_id === null);

        if (existing) {
            changeQuantity(existing.id, existing.quantity + 1, skuId);
            return;
        }

        router.post(
            `/admin/sales/checkout/${checkout!.id}/items`,
            { sku_id: skuId, quantity: 1 },
            {
                only: ['checkout'],
                preserveScroll: true,
                showProgress: false,
                onError: (errors) => setInlineError({ skuId, message: errors.sku_id ?? 'Could not add this item.' }),
                onFinish: () => setIsSubmitting(false),
            },
        );
    }

    function changeQuantity(itemId: number, quantity: number, skuId?: number) {
        setInlineError(null);
        setIsSubmitting(true);

        router.patch(
            `/admin/sales/checkout/${checkout!.id}/items/${itemId}`,
            { quantity },
            {
                only: ['checkout'],
                preserveScroll: true,
                showProgress: false,
                onError: (errors) =>
                    setInlineError({ skuId: skuId ?? itemId, message: errors.quantity ?? 'Could not update quantity.' }),
                onFinish: () => setIsSubmitting(false),
            },
        );
    }

    function removeItem(itemId: number) {
        setIsSubmitting(true);
        router.delete(`/admin/sales/checkout/${checkout!.id}/items/${itemId}`, {
            only: ['checkout'],
            preserveScroll: true,
            showProgress: false,
            onFinish: () => setIsSubmitting(false),
        });
    }

    function submitDiscount(e: FormEvent) {
        e.preventDefault();
        discountForm.transform((formData) => ({
            type: formData.type,
            source_id: parseInt(formData.source_id || '0', 10),
            amount_minor: Math.round(parseFloat(formData.amount_naira || '0') * 100),
        }));
        discountForm.post(`/admin/sales/checkout/${checkout.id}/discount`, {
            preserveScroll: true,
            onSuccess: () => setDiscountOpen(false),
        });
    }

    async function cancelCheckout() {
        const confirmed = await confirm({
            kind: 'danger',
            title: 'Cancel this checkout?',
            body: 'This releases every reserved item back to Available stock. The cart cannot be recovered after this.',
            confirmLabel: 'Cancel checkout',
            cancelLabel: 'Keep checkout',
        });

        if (!confirmed) {
            return;
        }

        router.post(`/admin/sales/checkout/${checkout.id}/cancel`);
    }

    function completeSale(e: FormEvent) {
        e.preventDefault();
        completeForm.post(`/admin/sales/checkout/${checkout.id}/complete`);
    }

    return (
        <AdminShell>
            <Head title="Checkout" />

            <AdminPageHead
                title={`Checkout — ${shop?.name ?? 'Unknown shop'}`}
                description={checkout.customer_name ?? 'Walk-in customer'}
                actions={<CheckoutExpiryTimer expiresAt={checkout.reservation_expires_at} onExpire={() => setIsExpired(true)} />}
            />

            {isExpired && (
                <div className="mb-4">
                    <AlertBanner kind="danger">
                        This checkout expired — start a new one.{' '}
                        <button type="button" onClick={() => router.visit('/admin/sales/checkout')} className="underline font-semibold">
                            Start new checkout
                        </button>
                    </AlertBanner>
                </div>
            )}

            <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_420px]">
                <div className={`bg-admin-surface border border-admin-border rounded-admin-card p-4 h-[520px] ${closed ? 'pointer-events-none opacity-60' : ''}`}>
                    <ProductSearch shopId={checkout.shop_id} onAdd={addItem} inlineError={inlineError} disabled={locked} />
                </div>

                <div className={`bg-admin-text rounded-admin-card p-4 flex flex-col ${closed ? 'pointer-events-none opacity-60' : ''}`}>
                    <div className="flex-1 min-h-0 mb-4">
                        <POSCart
                            items={checkout.items}
                            subtotalMinor={checkout.subtotal_minor}
                            discountMinor={checkout.discount_minor}
                            totalMinor={checkout.total_minor}
                            onRemoveItem={removeItem}
                            onChangeQuantity={changeQuantity}
                            disabled={locked}
                            loading={isSubmitting}
                        />
                    </div>

                    {discountOpen ? (
                        <form onSubmit={submitDiscount} className="border-t border-white/15 pt-3 mb-3">
                            <div className="grid grid-cols-2 gap-2 mb-2">
                                <select
                                    value={discountForm.data.type}
                                    onChange={(e) => discountForm.setData('type', e.target.value)}
                                    className="border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs bg-white"
                                >
                                    {DISCOUNT_TYPES.map((t) => (
                                        <option key={t.value} value={t.value}>
                                            {t.label}
                                        </option>
                                    ))}
                                </select>
                                <input
                                    type="number"
                                    step="0.01"
                                    placeholder="Amount (₦)"
                                    value={discountForm.data.amount_naira}
                                    onChange={(e) => discountForm.setData('amount_naira', e.target.value)}
                                    className="border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs bg-white"
                                />
                            </div>
                            {discountForm.errors.type && <p className="text-xs text-admin-red mb-2">{discountForm.errors.type}</p>}
                            <div className="flex gap-2">
                                <AdminButton type="submit" variant="neutral" isLoading={discountForm.processing}>
                                    Apply discount
                                </AdminButton>
                                <AdminButton type="button" variant="neutral" onClick={() => setDiscountOpen(false)}>
                                    Cancel
                                </AdminButton>
                            </div>
                        </form>
                    ) : (
                        <div className="flex items-center gap-3 mb-3">
                            <button
                                type="button"
                                disabled={locked}
                                onClick={() => setDiscountOpen(true)}
                                className="text-xs font-semibold text-admin-blue text-left disabled:text-white/40"
                            >
                                + Apply discount
                            </button>
                            <button
                                type="button"
                                disabled={locked}
                                onClick={() => router.visit(`/admin/warranty/trade-ins/create?checkout=${checkout.id}`)}
                                className="text-xs font-semibold text-admin-blue text-left disabled:text-white/40"
                            >
                                + Trade-in
                            </button>
                        </div>
                    )}

                    <form onSubmit={completeSale} className="border-t border-white/15 pt-3">
                        {pendingPaymentTransaction ? (
                            <div className="bg-admin-blue/10 border border-admin-blue/30 rounded-admin-button px-2 py-2 text-xs text-admin-text mb-2">
                                Customer submitted a bank transfer
                                {pendingPaymentTransaction.provider_reference ? ` (ref: ${pendingPaymentTransaction.provider_reference})` : ''} —
                                confirming below will complete the sale against it.
                            </div>
                        ) : (
                            <>
                                <select
                                    value={completeForm.data.payment_method}
                                    onChange={(e) => completeForm.setData('payment_method', e.target.value)}
                                    className="w-full border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs mb-2 bg-white"
                                >
                                    {PAYMENT_METHODS.map((m) => (
                                        <option key={m.value} value={m.value}>
                                            {m.label}
                                        </option>
                                    ))}
                                </select>
                                {completeForm.data.payment_method !== 'cash' && (
                                    <input
                                        type="text"
                                        placeholder="Reference (terminal/transfer ID)"
                                        value={completeForm.data.payment_reference}
                                        onChange={(e) => completeForm.setData('payment_reference', e.target.value)}
                                        className="w-full border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs mb-2 bg-white"
                                    />
                                )}
                            </>
                        )}
                        {completeForm.errors.payment_method && (
                            <p className="text-xs text-admin-red mb-2">{completeForm.errors.payment_method}</p>
                        )}

                        <div className="flex items-center gap-2">
                            <AdminButton
                                type="submit"
                                isLoading={completeForm.processing}
                                disabled={checkout.items.length === 0 || locked}
                            >
                                Complete sale
                            </AdminButton>
                            <AdminButton type="button" variant="danger-solid" onClick={cancelCheckout}>
                                Cancel checkout
                            </AdminButton>
                        </div>
                    </form>
                </div>
            </div>
        </AdminShell>
    );
}
