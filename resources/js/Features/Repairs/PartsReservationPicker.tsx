import { useEffect, useRef, useState } from 'react';
import { router } from '@inertiajs/react';
import Icon from '../../Components/Icons/Icon';
import AdminEmptyState from '../../Components/Admin/AdminEmptyState';
import AdminButton from '../../Components/Admin/AdminButton';
import { formatNaira } from '../../lib/money';

type PartSearchResult = {
    sku_id: number;
    sku_code: string;
    product_name: string;
    is_serialized: boolean;
    selling_price_minor: number;
    available: number;
};

export type PartReservationRow = {
    id: number;
    inventory_item_id: number | null;
    sku_id: number;
    quantity: number;
    status: string;
};

interface PartsReservationPickerProps {
    repairId: number;
    reservations: PartReservationRow[];
    disabled?: boolean;
}

/** SKU search + reserve, reusing the same InventoryCatalogQuery-backed search pattern as Sales' ProductSearch (Implementation Plan Phase 6 UI list). */
export default function PartsReservationPicker({ repairId, reservations, disabled = false }: PartsReservationPickerProps) {
    const [term, setTerm] = useState('');
    const [results, setResults] = useState<PartSearchResult[]>([]);
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
            fetch(`/admin/repairs/${repairId}/parts/search?q=${encodeURIComponent(term)}`, { credentials: 'same-origin' })
                .then((response) => response.json())
                .then((json: { results: PartSearchResult[] }) => {
                    if (requestId.current === currentRequest) {
                        setResults(json.results);
                        setLoading(false);
                    }
                })
                .catch(() => setLoading(false));
        }, 300);

        return () => clearTimeout(timeout);
    }, [term, repairId]);

    function reserve(skuId: number) {
        router.post(`/admin/repairs/${repairId}/parts`, { sku_id: skuId, quantity: 1 }, { preserveScroll: true });
    }

    function install(reservationId: number) {
        router.post(`/admin/repairs/${repairId}/parts/${reservationId}/install`, {}, { preserveScroll: true });
    }

    return (
        <div className="grid gap-6 lg:grid-cols-2">
            <div>
                <h3 className="text-sm font-semibold text-admin-text mb-2">Search parts</h3>
                <div className="relative mb-3">
                    <Icon name="search" className="absolute left-3 top-1/2 -translate-y-1/2 text-admin-text3" />
                    <input
                        type="text"
                        value={term}
                        disabled={disabled}
                        onChange={(e) => setTerm(e.target.value)}
                        placeholder="Search by SKU, brand, or model…"
                        className="w-full border border-admin-border-strong rounded-admin-button pl-9 pr-3 py-2 text-admin-field outline-none focus:border-admin-blue focus:ring-2 focus:ring-admin-focus"
                    />
                </div>

                {term.trim() === '' ? (
                    <AdminEmptyState icon="search" title="Search for a part to reserve" />
                ) : loading ? (
                    <p className="text-sm text-admin-text3">Searching…</p>
                ) : results.length === 0 ? (
                    <AdminEmptyState icon="box-seam" title="No matching parts" />
                ) : (
                    <ul className="flex flex-col gap-2">
                        {results.map((result) => (
                            <li key={result.sku_id} className="border border-admin-border rounded-admin-card p-3 flex items-center justify-between gap-3">
                                <div>
                                    <div className="text-sm font-medium text-admin-text">{result.product_name}</div>
                                    <div className="text-xs text-admin-text3">
                                        {result.sku_code} · {result.available} available · {formatNaira(result.selling_price_minor)}
                                    </div>
                                </div>
                                <AdminButton
                                    variant="neutral"
                                    disabled={disabled || result.available < 1}
                                    onClick={() => reserve(result.sku_id)}
                                >
                                    Reserve
                                </AdminButton>
                            </li>
                        ))}
                    </ul>
                )}
            </div>

            <div>
                <h3 className="text-sm font-semibold text-admin-text mb-2">Reserved parts</h3>
                {reservations.length === 0 ? (
                    <AdminEmptyState icon="tools" title="No parts reserved yet" />
                ) : (
                    <ul className="border border-admin-border rounded-admin-card divide-y divide-admin-border">
                        {reservations.map((reservation) => (
                            <li key={reservation.id} className="p-3 flex items-center justify-between gap-3">
                                <div className="text-sm text-admin-text">
                                    SKU #{reservation.sku_id} · Qty {reservation.quantity}
                                    <span className="ml-2 text-xs font-semibold uppercase text-admin-text3">{reservation.status}</span>
                                </div>
                                {reservation.status === 'reserved' && !disabled && (
                                    <AdminButton variant="neutral" onClick={() => install(reservation.id)}>
                                        Mark installed
                                    </AdminButton>
                                )}
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </div>
    );
}
