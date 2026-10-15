import { useEffect, useRef, useState } from 'react';
import Icon from '../../Components/Icons/Icon';
import AdminEmptyState from '../../Components/Admin/AdminEmptyState';
import AdminSelect from '../../Components/Admin/Forms/AdminSelect';
import { formatNaira } from '../../lib/money';

export type ProductSearchResult = {
    sku_id: number;
    sku_code: string;
    product_name: string;
    is_serialized: boolean;
    selling_price_minor: number;
    available: number;
    /** What tells this unit apart from a sibling variant of the same product, e.g. "Black · 128GB". Empty when the product has no variants. */
    variant_label: string;
};

interface ProductSearchProps {
    shopId: number;
    /** Category names from the admin's list, for the filter dropdown. */
    categories: string[];
    /** Brands of products that can be sold, for the filter dropdown. */
    brands: string[];
    onAdd: (skuId: number) => void;
    /** Per-SKU inline error (UI/UX §14C.3: insufficient stock is local to that product's result, not a page banner). */
    inlineError?: { skuId: number; message: string } | null;
    disabled?: boolean;
}

/**
 * Left pane of the POS Checkout screen (UI/UX §14C.3). Four ways in, each for a
 * different case: type a name, SKU or IMEI to find one item; narrow by category
 * or brand to browse a group; then narrow by model to tell variants (colour,
 * storage, grade) of the same product apart. Any one of them is enough to search.
 */
export default function ProductSearch({ shopId, categories, brands, onAdd, inlineError, disabled = false }: ProductSearchProps) {
    const [term, setTerm] = useState('');
    const [category, setCategory] = useState('');
    const [brand, setBrand] = useState('');
    const [model, setModel] = useState('');
    const [models, setModels] = useState<string[]>([]);
    const [results, setResults] = useState<ProductSearchResult[]>([]);
    const [loading, setLoading] = useState(false);
    const requestId = useRef(0);
    const modelsRequestId = useRef(0);

    const hasFilter = term.trim() !== '' || category !== '' || brand !== '' || model !== '';

    // The model list narrows to whichever category/brand is already picked, and resets the model choice if it no longer applies.
    useEffect(() => {
        const currentRequest = ++modelsRequestId.current;
        const params = new URLSearchParams();
        if (category !== '') params.set('category', category);
        if (brand !== '') params.set('brand', brand);

        fetch(`/admin/sales/models?${params.toString()}`, { credentials: 'same-origin' })
            .then((response) => response.json())
            .then((json: { results: string[] }) => {
                if (modelsRequestId.current !== currentRequest) return;
                setModels(json.results);
                setModel((current) => (json.results.includes(current) ? current : ''));
            })
            .catch(() => {});
    }, [category, brand]);

    useEffect(() => {
        if (!hasFilter) {
            setResults([]);
            return;
        }

        const currentRequest = ++requestId.current;
        const timeout = setTimeout(() => {
            setLoading(true);

            const params = new URLSearchParams({ shop_id: String(shopId) });
            if (term.trim() !== '') params.set('q', term.trim());
            if (category !== '') params.set('category', category);
            if (brand !== '') params.set('brand', brand);
            if (model !== '') params.set('model', model);

            fetch(`/admin/sales/search?${params.toString()}`, { credentials: 'same-origin' })
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
    }, [term, category, brand, model, shopId, hasFilter]);

    return (
        <div className="flex flex-col h-full">
            <div className="relative mb-2">
                <Icon name="search" className="absolute left-3 top-1/2 -translate-y-1/2 text-admin-text3" />
                <input
                    type="text"
                    value={term}
                    onChange={(e) => setTerm(e.target.value)}
                    placeholder="Name, SKU or IMEI — or scan…"
                    autoFocus
                    className="w-full border border-admin-border-strong rounded-admin-button pl-9 pr-3 py-2 text-admin-field outline-none focus:border-admin-blue focus:ring-2 focus:ring-admin-focus"
                />
            </div>

            <div className="grid grid-cols-3 gap-2 mb-3">
                <AdminSelect aria-label="Filter by category" value={category} onChange={(e) => setCategory(e.target.value)}>
                    <option value="">All categories</option>
                    {categories.map((name) => (
                        <option key={name} value={name}>
                            {name}
                        </option>
                    ))}
                </AdminSelect>
                <AdminSelect aria-label="Filter by brand" value={brand} onChange={(e) => setBrand(e.target.value)}>
                    <option value="">All brands</option>
                    {brands.map((name) => (
                        <option key={name} value={name}>
                            {name}
                        </option>
                    ))}
                </AdminSelect>
                <AdminSelect aria-label="Filter by model (variant)" value={model} onChange={(e) => setModel(e.target.value)} disabled={models.length === 0}>
                    <option value="">All models</option>
                    {models.map((name) => (
                        <option key={name} value={name}>
                            {name}
                        </option>
                    ))}
                </AdminSelect>
            </div>

            {!hasFilter ? (
                <AdminEmptyState icon="search" title="Search, or pick a category, brand or model" />
            ) : loading ? (
                <p className="text-sm text-admin-text3">Searching…</p>
            ) : results.length === 0 ? (
                <AdminEmptyState icon="box-seam" title="No matching products" description="Try a different name, SKU, IMEI, category, brand or model." />
            ) : (
                <ul className="flex flex-col gap-2 overflow-y-auto">
                    {results.map((result) => (
                        <li key={result.sku_id} className="border border-admin-border rounded-admin-card p-3">
                            <div className="flex items-start justify-between gap-3">
                                <div className="min-w-0">
                                    <div className="text-sm font-medium text-admin-text truncate">{result.product_name}</div>
                                    {result.variant_label && (
                                        <div className="text-xs font-medium text-admin-blue truncate">{result.variant_label}</div>
                                    )}
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
