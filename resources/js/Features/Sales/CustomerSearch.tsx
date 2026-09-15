import { useEffect, useRef, useState } from 'react';
import Icon from '../../Components/Icons/Icon';

export type CustomerSearchResult = {
    id: number;
    name: string;
    email: string | null;
    phone: string;
};

interface CustomerSearchProps {
    value: CustomerSearchResult | null;
    onChange: (customer: CustomerSearchResult | null) => void;
    error?: string;
    disabled?: boolean;
    /** Defaults to Sales' own endpoint — Repair intake (and any future caller) passes its own permission-gated search route. */
    searchUrl?: string;
}

/**
 * Searches registered customers by name/email/phone
 * (Domain\Identity\Application\Contracts\CustomerDirectoryQuery) so a
 * staff member never has to know or type a customer's internal database
 * ID. Originated on Checkout (selecting nobody keeps it a walk-in sale)
 * and reused as-is by Repair intake.
 */
export default function CustomerSearch({ value, onChange, error, disabled = false, searchUrl = '/admin/sales/customers/search' }: CustomerSearchProps) {
    const [term, setTerm] = useState('');
    const [results, setResults] = useState<CustomerSearchResult[]>([]);
    const [loading, setLoading] = useState(false);
    const [open, setOpen] = useState(false);
    const requestId = useRef(0);
    const containerRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (term.trim() === '') {
            setResults([]);
            return;
        }

        const currentRequest = ++requestId.current;
        const timeout = setTimeout(() => {
            setLoading(true);
            fetch(`${searchUrl}?q=${encodeURIComponent(term)}`, { credentials: 'same-origin' })
                .then((response) => response.json())
                .then((json: { results: CustomerSearchResult[] }) => {
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

    if (value) {
        return (
            <div className="flex flex-col mb-5">
                <span className="text-xs font-semibold text-admin-text mb-[5px]">Customer</span>
                <div className="flex items-center justify-between gap-2 border border-admin-border-strong rounded-admin-button px-admin-field-x py-admin-field-y bg-white">
                    <div className="min-w-0">
                        <div className="text-admin-field text-admin-text font-medium truncate">{value.name}</div>
                        {(value.email || value.phone) && (
                            <div className="text-xs text-admin-text3 truncate">{value.email ?? value.phone}</div>
                        )}
                    </div>
                    <button
                        type="button"
                        disabled={disabled}
                        onClick={() => onChange(null)}
                        aria-label="Remove customer — back to walk-in"
                        className="shrink-0 text-admin-text3 hover:text-admin-text disabled:cursor-not-allowed"
                    >
                        <Icon name="x-lg" />
                    </button>
                </div>
            </div>
        );
    }

    return (
        <div className="flex flex-col mb-5 relative" ref={containerRef}>
            <span className="text-xs font-semibold text-admin-text mb-[5px]">Customer (optional — blank is walk-in)</span>
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
                    placeholder="Search by name, email, or phone…"
                    className={`
                        w-full bg-white border rounded-admin-button pl-9 pr-3 py-admin-field-y
                        font-admin-base text-admin-field text-admin-text placeholder-admin-text3
                        outline-none transition-all duration-200
                        ${error ? 'border-admin-red focus:border-admin-red focus:ring-2 focus:ring-admin-red-soft' : 'border-admin-border-strong focus:border-admin-blue focus:ring-2 focus:ring-admin-focus'}
                    `}
                />
            </div>
            {error && <p className="text-xs text-admin-red mt-1">{error}</p>}

            {open && term.trim() !== '' && (
                <div className="absolute top-full left-0 right-0 mt-1 z-20 bg-admin-surface border border-admin-border rounded-admin-card shadow-admin-modal max-h-64 overflow-y-auto">
                    {loading ? (
                        <p className="text-sm text-admin-text3 p-3">Searching…</p>
                    ) : results.length === 0 ? (
                        <p className="text-sm text-admin-text3 p-3">No matching customers.</p>
                    ) : (
                        <ul>
                            {results.map((customer) => (
                                <li key={customer.id}>
                                    <button
                                        type="button"
                                        onClick={() => {
                                            onChange(customer);
                                            setTerm('');
                                            setOpen(false);
                                        }}
                                        className="w-full text-left px-3 py-2 hover:bg-admin-hover"
                                    >
                                        <div className="text-sm font-medium text-admin-text truncate">{customer.name}</div>
                                        <div className="text-xs text-admin-text3 truncate">{customer.email ?? customer.phone}</div>
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
