import { useEffect, useRef, useState } from 'react';
import Icon from '../../Components/Icons/Icon';
import { formatNaira } from '../../lib/money';

export type FaultSearchResult = {
    id: number;
    label: string;
    /** Present only for the system-wide (diagnosis) search — disambiguates "Battery drain" on three different phones. */
    device_type_label?: string;
    device_model_label?: string | null;
    /** Present only for the device-specific (repair completion) search. */
    commission_amount_minor?: number | null;
};

interface FaultPickerProps {
    searchUrl: string;
    onSelect: (fault: FaultSearchResult) => void;
    /** Already-added labels, filtered out of the results so the same fault can't be picked twice. */
    excludeLabels?: string[];
    disabled?: boolean;
    placeholder?: string;
}

/**
 * A searchable combobox over the device-problem-tag catalog, debounced
 * AJAX search — same pattern as this session's own IconPicker.tsx/
 * PartsReservationPicker.tsx. Used in two shapes depending on which
 * search endpoint it's pointed at: system-wide (diagnosis's revealed
 * faults — results carry device labels) or device-specific (a repair's
 * actually-fixed faults — results carry a commission amount).
 */
export default function FaultPicker({ searchUrl, onSelect, excludeLabels = [], disabled = false, placeholder = 'Search faults…' }: FaultPickerProps) {
    const [term, setTerm] = useState('');
    const [results, setResults] = useState<FaultSearchResult[]>([]);
    const [loading, setLoading] = useState(false);
    const [open, setOpen] = useState(false);
    const requestId = useRef(0);
    const containerRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        const currentRequest = ++requestId.current;
        const timeout = setTimeout(() => {
            setLoading(true);
            fetch(`${searchUrl}?q=${encodeURIComponent(term)}`, { credentials: 'same-origin' })
                .then((response) => response.json())
                .then((json: { results: FaultSearchResult[] }) => {
                    if (requestId.current === currentRequest) {
                        setResults(json.results);
                        setLoading(false);
                    }
                })
                .catch(() => setLoading(false));
        }, 300);

        return () => clearTimeout(timeout);
    }, [term, searchUrl]);

    useEffect(() => {
        function handleClickOutside(e: MouseEvent) {
            if (containerRef.current && !containerRef.current.contains(e.target as Node)) {
                setOpen(false);
            }
        }

        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    const excluded = new Set(excludeLabels);
    const visibleResults = results.filter((result) => !excluded.has(result.label));

    function select(result: FaultSearchResult) {
        onSelect(result);
        setTerm('');
        setOpen(false);
    }

    return (
        <div className="relative" ref={containerRef}>
            <div className="relative">
                <Icon name="search" className="absolute left-3 top-1/2 -translate-y-1/2 text-admin-text3" />
                <input
                    type="text"
                    value={term}
                    disabled={disabled}
                    onChange={(e) => {
                        setTerm(e.target.value);
                        setOpen(true);
                    }}
                    onFocus={() => setOpen(true)}
                    placeholder={placeholder}
                    className="w-full border border-admin-border-strong rounded-admin-button pl-9 pr-3 py-2 text-sm outline-none focus:border-admin-blue focus:ring-2 focus:ring-admin-focus disabled:bg-admin-hover disabled:cursor-not-allowed"
                />
            </div>

            {open && (
                <div className="absolute z-20 mt-1 w-full bg-white border border-admin-border rounded-admin-card shadow-admin-modal max-h-64 overflow-y-auto">
                    {loading ? (
                        <p className="text-sm text-admin-text3 p-3">Searching…</p>
                    ) : visibleResults.length === 0 ? (
                        <p className="text-sm text-admin-text3 p-3">{term.trim() === '' ? 'Type to search, or browse recent faults.' : 'No matching faults.'}</p>
                    ) : (
                        <ul>
                            {visibleResults.map((result) => (
                                <li key={result.id}>
                                    <button
                                        type="button"
                                        onClick={() => select(result)}
                                        className="w-full text-left px-3 py-2 hover:bg-admin-hover flex items-center justify-between gap-2"
                                    >
                                        <div className="min-w-0">
                                            <div className="text-sm font-medium text-admin-text truncate">{result.label}</div>
                                            {result.device_type_label && (
                                                <div className="text-xs text-admin-text3 truncate">
                                                    {result.device_model_label ?? result.device_type_label}
                                                </div>
                                            )}
                                        </div>
                                        {result.commission_amount_minor != null && (
                                            <span className="text-xs text-admin-text3 shrink-0">{formatNaira(result.commission_amount_minor)}</span>
                                        )}
                                    </button>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            )}
        </div>
    );
}
