import Icon from '../Icons/Icon';
import CategoryDropdown from './CategoryDropdown';

interface ShopSearchBarProps {
    search: string;
    onSearchChange: (value: string) => void;
    category: string;
    onCategoryChange: (value: string) => void;
    categories: string[];
}

/**
 * The search pill (live, directly-typable input, circular blue search
 * button on the right) stays the same shape at every size. The category
 * picker doesn't: on `sm:` and up there's room for it inside the same
 * pill (`CategoryDropdown`'s "embedded" trigger); below that it moves
 * out to its own circular filter button next to the pill (`CategoryDropdown`'s
 * "icon" trigger) rather than being squeezed in and truncating the input.
 */
export default function ShopSearchBar({ search, onSearchChange, category, onCategoryChange, categories }: ShopSearchBarProps) {
    return (
        <div className="flex items-center gap-2 max-w-xl">
            <div className="flex-1 flex items-center bg-white border border-gray-200 rounded-full shadow-sm pl-4 pr-1 py-1 gap-2 min-w-0">
                <input
                    type="text"
                    value={search}
                    onChange={(e) => onSearchChange(e.target.value)}
                    placeholder="Search by brand or model…"
                    className="flex-1 min-w-0 bg-transparent border-0 outline-none text-sm text-customer-text placeholder-customer-text2 py-1"
                />

                <div className="hidden sm:flex items-center gap-2 shrink-0">
                    <div className="w-px h-5 bg-gray-200" />
                    <CategoryDropdown category={category} onCategoryChange={onCategoryChange} categories={categories} variant="embedded" />
                </div>

                <span className="w-8 h-8 rounded-full bg-customer-blue text-white flex items-center justify-center shrink-0 transition-all duration-150 hover:scale-110 hover:bg-blue-700">
                    <Icon name="search" className="text-xs" />
                </span>
            </div>

            <div className="sm:hidden">
                <CategoryDropdown category={category} onCategoryChange={onCategoryChange} categories={categories} variant="icon" />
            </div>
        </div>
    );
}
