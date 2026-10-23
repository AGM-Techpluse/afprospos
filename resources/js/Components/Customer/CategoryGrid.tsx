import Icon from '../Icons/Icon';
import { placeholderIcon } from '../ProductImage';

interface CategoryGridProps {
    category: string;
    onSelect: (value: string) => void;
    categories: string[];
    columns?: 2 | 3;
}

/** The selectable category-card grid shared by `CategoryDropdown`'s desktop popover and `ShopSearchBar`'s mobile full-screen search panel — same cards, different containers. */
export default function CategoryGrid({ category, onSelect, categories, columns = 3 }: CategoryGridProps) {
    return (
        <div className={`grid gap-2 ${columns === 2 ? 'grid-cols-2' : 'grid-cols-3'}`}>
            <button
                type="button"
                onClick={() => onSelect('')}
                className={`flex flex-col items-center gap-1.5 rounded-xl p-2.5 transition-colors ${
                    category === '' ? 'bg-customer-blue-soft' : 'hover:bg-gray-50'
                }`}
            >
                <span className="w-10 h-10 rounded-full bg-customer-blue text-white flex items-center justify-center text-base">
                    <Icon name="grid" />
                </span>
                <span className="text-[11px] font-bold text-customer-text text-center truncate w-full">All</span>
            </button>

            {categories.map((name) => (
                <button
                    key={name}
                    type="button"
                    onClick={() => onSelect(name)}
                    className={`flex flex-col items-center gap-1.5 rounded-xl p-2.5 transition-colors ${
                        category === name ? 'bg-customer-blue-soft' : 'hover:bg-gray-50'
                    }`}
                >
                    <span className="w-10 h-10 rounded-full bg-customer-blue-soft text-customer-blue flex items-center justify-center text-base">
                        <Icon name={placeholderIcon(name)} />
                    </span>
                    <span className="text-[11px] font-bold text-customer-text text-center truncate w-full">{name}</span>
                </button>
            ))}
        </div>
    );
}
