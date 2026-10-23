import { useEffect, useRef, useState } from 'react';
import Icon from '../Icons/Icon';
import { placeholderIcon } from '../ProductImage';
import CategoryGrid from './CategoryGrid';

interface CategoryDropdownProps {
    category: string;
    onCategoryChange: (value: string) => void;
    categories: string[];
    className?: string;
    /**
     * 'embedded' — a text-label trigger with no border/background of its
     * own, meant to sit flush inside `ShopSearchBar`'s desktop pill.
     * 'icon' — a standalone circular filter button (sliders icon, a dot
     * when a category is active), used on its own — `ShopSearchBar`'s
     * mobile layout, where there's no pill to embed into.
     */
    variant?: 'embedded' | 'icon';
}

/**
 * A card-grid category picker behind two different triggers depending
 * on where it's used — same debounced-popover family
 * (click-outside-to-close) as `CustomerSearch`/`FaultPicker`. The panel
 * itself is identical either way, capped to the viewport width
 * (`min(90vw, …)`) so it's safe at any screen size.
 */
export default function CategoryDropdown({ category, onCategoryChange, categories, className = '', variant = 'icon' }: CategoryDropdownProps) {
    const [isOpen, setIsOpen] = useState(false);
    const containerRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        function handleClickOutside(e: MouseEvent) {
            if (containerRef.current && !containerRef.current.contains(e.target as Node)) {
                setIsOpen(false);
            }
        }

        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    function select(value: string) {
        onCategoryChange(value);
        setIsOpen(false);
    }

    return (
        <div ref={containerRef} className={`relative shrink-0 ${className}`}>
            {variant === 'icon' ? (
                <button
                    type="button"
                    onClick={() => setIsOpen((open) => !open)}
                    aria-label="Filter by category"
                    className="relative w-10 h-10 rounded-full bg-white border border-gray-200 flex items-center justify-center transition-all duration-150 hover:scale-105 hover:border-customer-blue"
                >
                    <Icon name="sliders" className="text-customer-text" />
                    {category !== '' && (
                        <span className="absolute top-0.5 right-0.5 w-2.5 h-2.5 rounded-full bg-customer-blue border-2 border-white" />
                    )}
                </button>
            ) : (
                <button
                    type="button"
                    onClick={() => setIsOpen((open) => !open)}
                    className="flex items-center gap-1.5 pr-1 text-left rounded-full px-1.5 py-1 transition-colors hover:bg-gray-50"
                >
                    <span className="w-7 h-7 rounded-full bg-customer-blue-soft text-customer-blue flex items-center justify-center text-sm shrink-0">
                        <Icon name={category ? placeholderIcon(category) : 'grid'} />
                    </span>
                    <span className="min-w-0 truncate max-w-[90px] text-sm font-semibold text-customer-text">
                        {category || 'All categories'}
                    </span>
                    <Icon name={isOpen ? 'chevron-up' : 'chevron-down'} className="text-customer-text2 text-xs shrink-0" />
                </button>
            )}

            {isOpen && (
                <div className="absolute z-30 right-0 mt-2 w-[min(90vw,18rem)] bg-white rounded-2xl shadow-customer-strong border border-gray-100 p-3">
                    <p className="text-[11px] font-bold text-customer-text2 uppercase tracking-wide mb-2 px-1">Category</p>
                    <div className="max-h-72 overflow-y-auto">
                        <CategoryGrid category={category} onSelect={select} categories={categories} columns={3} />
                    </div>
                </div>
            )}
        </div>
    );
}
