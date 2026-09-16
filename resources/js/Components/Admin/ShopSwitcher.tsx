import { useEffect, useRef, useState } from 'react';
import { router } from '@inertiajs/react';
import Icon from '../Icons/Icon';

type Shop = { id: number; name: string; sku_prefix_code: string };

interface ShopSwitcherProps {
    accessible: Shop[];
    active: Shop | null;
}

export default function ShopSwitcher({ accessible = [], active = null }: ShopSwitcherProps) {
    const [open, setOpen] = useState(false);
    const [switching, setSwitching] = useState(false);
    const containerRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (!open) return;

        const handleClickOutside = (e: MouseEvent) => {
            if (containerRef.current && !containerRef.current.contains(e.target as Node)) {
                setOpen(false);
            }
        };
        const handleEscape = (e: KeyboardEvent) => {
            if (e.key === 'Escape') setOpen(false);
        };

        document.addEventListener('mousedown', handleClickOutside);
        document.addEventListener('keydown', handleEscape);
        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
            document.removeEventListener('keydown', handleEscape);
        };
    }, [open]);

    if (accessible.length === 0) {
        return null;
    }

    function switchShop(shopId: number) {
        setOpen(false);
        setSwitching(true);
        router.post(
            '/admin/shops/switch',
            { shop_id: shopId },
            { preserveScroll: true, onFinish: () => setSwitching(false) },
        );
    }

    if (accessible.length === 1) {
        return (
            <div className="flex items-center gap-1.5 text-sm font-medium text-admin-text2">
                <Icon name="shop" />
                <span className="truncate max-w-[160px]">{accessible[0].name}</span>
            </div>
        );
    }

    return (
        <div className="relative" ref={containerRef}>
            <button
                type="button"
                onClick={() => setOpen((current) => !current)}
                aria-label="Switch shop"
                aria-expanded={open}
                disabled={switching}
                className="flex items-center gap-1.5 px-2.5 py-1.5 rounded-admin-button text-sm font-medium text-admin-text2 hover:bg-admin-hover hover:text-admin-text disabled:opacity-60"
            >
                <Icon name="shop" />
                <span className="truncate max-w-[160px]">{active ? active.name : 'Select a shop'}</span>
                <Icon name="chevron-down" className="text-xs" />
            </button>

            {open && (
                <div
                    role="menu"
                    className="absolute left-0 top-full mt-2 w-64 bg-admin-surface rounded-admin-card border border-admin-border py-1 z-50 max-h-80 overflow-y-auto"
                >
                    <div className="px-3 py-2 border-b border-admin-border">
                        <p className="text-xs font-semibold text-admin-text2 uppercase tracking-wide">Your shops</p>
                    </div>
                    {accessible.map((shop) => (
                        <button
                            key={shop.id}
                            type="button"
                            onClick={() => switchShop(shop.id)}
                            className={`w-full flex items-center justify-between gap-2 px-3 py-2 text-sm text-left hover:bg-admin-hover ${
                                active?.id === shop.id ? 'text-admin-blue font-semibold' : 'text-admin-text'
                            }`}
                        >
                            <span className="truncate">{shop.name}</span>
                            {active?.id === shop.id && <Icon name="check-lg" />}
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}
