import { useEffect, useRef, useState } from 'react';
import Icon from '../../Components/Icons/Icon';
import AdminEmptyState from '../../Components/Admin/AdminEmptyState';
import { formatNaira } from '../../lib/money';

export type ProductSearchResult = {
    sku_id: number;
    sku_code: string;
    product_name: string;
    is_serialized: boolean;
    selling_price_minor: number;
    available: number;
};

interface ProductSearchProps {
    shopId: number;
    onAdd: (skuId: number) => void;
    /** Per-SKU inline error (UI/UX §14C.3: insufficient stock is local to that product's result, not a page banner). */
    inlineError?: { skuId: number; message: string } | null;
    disabled?: boolean;
}

/** Left pane of the POS Checkout screen (UI/UX §14C.3) — live catalog search scoped to the checkout's locked shop. */
export default function ProductSearch({ shopId, onAdd, inlineError, disabled = false }: ProductSearchProps) {
    const [term, setTerm] = useState('');
    const [results, setResults] = useState<ProductSearchResult[]>([]);
    const [loading, setLoading] = useState(false);
    const requestId = useRef(0);

    useEffect(() => {
        if (term.trim() === '') {
            setResults([]);
            return;
        }

        const currentRequest = ++requestId.current;
        const timeout = setTimeout(() => {
            setLoading(true);
            fetch(`/admin/sales/search?q=${encodeURIComponent(term)}&shop_id=${shopId}`, { credentials: 'same-origin' })
                .then((response) => response.json())
                .then((json: { results: ProductSearchResult[] }) => {
                    if (requestId.current === currentRequest) {
                        setResults(json.results);
                        setLoading(false);
                    }
                })
                .catch(() => setLoading(false));
        }, 300);

        return () => clearTimeout(timeout);
    }, [term, shopId]);

    return (
        <div className="flex flex-col h-full">
            <div className="relative mb-3">
                <Icon name="search" className="absolute left-3 top-1/2 -translate-y-1/2 text-admin-text3" />
                <input
                    type="text"
                    value={term}
                    onChange={(e) => setTerm(e.target.value)}
                    placeholder="Search or scan a product…"
                    autoFocus
                    className="w-full border border-admin-border-strong rounded-admin-button pl-9 pr-3 py-2 text-admin-field outline-none focus:border-admin-blue focus:ring-2 focus:ring-admin-focus"
                />
            </div>

            {term.trim() === '' ? (
                <AdminEmptyState icon="search" title="Search or scan a product to begin" />
            ) : loading ? (
                <p className="text-sm text-admin-text3">Searching…</p>
            ) : results.length === 0 ? (
                <AdminEmptyState icon="box-seam" title="No matching products" description="Try a different SKU, brand, or model." />
            ) : (
                <ul className="flex flex-col gap-2 overflow-y-auto">
                    {results.map((result) => (
                        <li key={result.sku_id} className="border border-admin-border rounded-admin-card p-3">
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <div className="text-sm font-medium text-admin-text">{result.product_name}</div>
                                    <div className="text-xs text-admin-text3">
                                        {result.sku_code} · {result.available} available
                                    </div>
                                </div>
                                <div className="text-right shrink-0">
                                    <div className="text-sm font-mono text-admin-text">{formatNaira(result.selling_price_minor)}</div>
                                    <button
                                        type="button"
                                        disabled={disabled || result.available < 1}
                                        onClick={() => onAdd(result.sku_id)}
                                        className="mt-1 text-xs font-semibold text-admin-blue disabled:text-admin-text3 disabled:cursor-not-allowed"
                                    >
                                        Add to cart
                                    </button>
                                </div>
                            </div>
                            {inlineError?.skuId === result.sku_id && (
                                <p className="text-xs text-admin-red mt-1.5">{inlineError.message}</p>
                            )}
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
