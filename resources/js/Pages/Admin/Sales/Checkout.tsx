import { FormEvent, useEffect, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { useConfirm } from '../../../hooks/useConfirm';
import AdminShell from '../../../Components/Admin/AdminShell';
import AdminPageHead from '../../../Components/Admin/AdminPageHead';
import AdminButton from '../../../Components/Admin/AdminButton';
import AdminSelect from '../../../Components/Admin/Forms/AdminSelect';
import AdminInput from '../../../Components/Admin/Forms/AdminInput';
import AlertBanner from '../../../Components/Feedback/AlertBanner';
import AdminEmptyState from '../../../Components/Admin/AdminEmptyState';
import ProductSearch from '../../../Features/Sales/ProductSearch';
import { formatNaira } from '../../../lib/money';
import POSCart, { CheckoutItemView } from '../../../Features/Sales/POSCart';
import CheckoutExpiryTimer from '../../../Features/Sales/CheckoutExpiryTimer';

type Shop = { id: number; name: string; sku_prefix_code: string };

type CheckoutView = {
    id: number;
    shop_id: number;
    customer_id: number | null;
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

interface CheckoutPageProps {
    checkout: CheckoutView | null;
    shops: Shop[];
    checkoutReservationMinutes: number;
    recentSales: RecentSale[];
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
            <AdminInput
                label="Customer ID (optional — blank is walk-in)"
                value={data.customer_id}
                onChange={(e) => setData('customer_id', e.target.value)}
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
            <div className="border border-admin-border rounded-admin-card">
                <AdminEmptyState icon="receipt" title="No recent sales" description="Your completed sales will show up here." />
            </div>
        );
    }

    return (
        <ul className="border border-admin-border rounded-admin-card divide-y divide-admin-border">
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

export default function SalesCheckout({ checkout, shops, recentSales }: CheckoutPageProps) {
    const confirm = useConfirm();
    const [inlineError, setInlineError] = useState<{ skuId: number; message: string } | null>(null);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [isExpired, setIsExpired] = useState(false);
    const [discountOpen, setDiscountOpen] = useState(false);

    const discountForm = useForm({ type: 'promotion', source_id: '1', amount_naira: '' });
    const completeForm = useForm({ payment_method: 'cash', payment_reference: '' });

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
                <div className="grid gap-8 lg:grid-cols-[minmax(0,1fr)_360px] items-start max-w-[900px] mx-auto">
                    <div>
                        <h2 className="text-sm font-semibold text-admin-text mb-3">Your recent sales</h2>
                        <RecentSales sales={recentSales} />
                    </div>
                    <NewCheckoutForm shops={shops} />
                </div>
            </AdminShell>
        );
    }

    const shop = shops.find((s) => s.id === checkout.shop_id);
    const locked = isSubmitting || isExpired || checkout.status !== 'open';

    function addItem(skuId: number) {
        setInlineError(null);
        setIsSubmitting(true);

        router.post(
            `/admin/sales/checkout/${checkout!.id}/items`,
            { sku_id: skuId, quantity: 1 },
            {
                preserveScroll: true,
                onError: (errors) => setInlineError({ skuId, message: errors.sku_id ?? 'Could not add this item.' }),
                onFinish: () => setIsSubmitting(false),
            },
        );
    }

    function removeItem(itemId: number) {
        setIsSubmitting(true);
        router.delete(`/admin/sales/checkout/${checkout!.id}/items/${itemId}`, {
            preserveScroll: true,
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
                title={`Checkout — ${shop?.name ?? `Shop #${checkout.shop_id}`}`}
                description={checkout.customer_id ? `Customer #${checkout.customer_id}` : 'Walk-in customer'}
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

            <div className={`grid gap-6 lg:grid-cols-[minmax(0,1fr)_420px] ${locked ? 'pointer-events-none opacity-60' : ''}`}>
                <div className="border border-admin-border rounded-admin-card p-4 h-[520px]">
                    <ProductSearch shopId={checkout.shop_id} onAdd={addItem} inlineError={inlineError} disabled={locked} />
                </div>

                <div className="border border-admin-border rounded-admin-card p-4 flex flex-col">
                    <div className="flex-1 min-h-0 mb-4">
                        <POSCart
                            items={checkout.items}
                            subtotalMinor={checkout.subtotal_minor}
                            discountMinor={checkout.discount_minor}
                            totalMinor={checkout.total_minor}
                            onRemoveItem={removeItem}
                            disabled={locked}
                        />
                    </div>

                    {discountOpen ? (
                        <form onSubmit={submitDiscount} className="border-t border-admin-border pt-3 mb-3">
                            <div className="grid grid-cols-2 gap-2 mb-2">
                                <select
                                    value={discountForm.data.type}
                                    onChange={(e) => discountForm.setData('type', e.target.value)}
                                    className="border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs"
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
                                    className="border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs"
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
                        <button
                            type="button"
                            disabled={locked}
                            onClick={() => setDiscountOpen(true)}
                            className="text-xs font-semibold text-admin-blue text-left mb-3 disabled:text-admin-text3"
                        >
                            + Apply discount
                        </button>
                    )}

                    <form onSubmit={completeSale} className="border-t border-admin-border pt-3">
                        <select
                            value={completeForm.data.payment_method}
                            onChange={(e) => completeForm.setData('payment_method', e.target.value)}
                            className="w-full border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs mb-2"
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
                                className="w-full border border-admin-border-strong rounded-admin-button px-2 py-1.5 text-xs mb-2"
                            />
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
                            <AdminButton type="button" variant="neutral" onClick={cancelCheckout}>
                                Cancel checkout
                            </AdminButton>
                        </div>
                    </form>
                </div>
            </div>
        </AdminShell>
    );
}
