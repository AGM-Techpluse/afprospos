import Icon from './Icons/Icon';

interface ProductImageProps {
    src: string | null;
    alt: string;
    /** 'square' for grid/list thumbnails, 'wide' for a product detail hero. */
    aspect?: 'square' | 'wide';
    /** Picks a closer-than-generic placeholder icon when there's no photo (e.g. a laptop silhouette for "Laptops") — purely cosmetic, falls back to a generic image icon for anything unmatched. */
    category?: string | null;
    className?: string;
}

const ASPECT_CLASS: Record<'square' | 'wide', string> = {
    square: 'aspect-square',
    wide: 'aspect-[4/3]',
};

/** Substring-matched against the category, case-insensitively — order matters (checked top to bottom), so put more specific terms first. */
const CATEGORY_ICONS: [string, string][] = [
    ['smartwatch', 'smartwatch'],
    ['watch', 'smartwatch'],
    ['tablet', 'tablet'],
    ['laptop', 'laptop'],
    ['phone', 'phone'],
    ['drone', 'airplane'],
    ['console', 'joystick'],
    ['gaming', 'joystick'],
    ['camera', 'camera'],
    ['headphone', 'headphones'],
    ['speaker', 'speaker'],
    ['charger', 'battery-charging'],
    ['accessor', 'bag'],
    ['part', 'tools'],
];

/** Exported for the storefront's "Shop by category" icon shortcuts — same heuristic, no need for a second mapping. */
export function placeholderIcon(category?: string | null): string {
    if (!category) return 'image';

    const lower = category.toLowerCase();
    const match = CATEGORY_ICONS.find(([needle]) => lower.includes(needle));

    return match?.[1] ?? 'image';
}

/**
 * One place to fix "images don't adjust to the space" — a fixed-ratio box
 * with `object-cover` so every product photo, whatever its source
 * dimensions, fills its slot consistently instead of stretching or
 * floating at its native size. Theme-agnostic (no Admin or Customer color
 * tokens) since it's used across both the Admin and public/Customer UIs.
 */
export default function ProductImage({ src, alt, aspect = 'square', category = null, className = '' }: ProductImageProps) {
    // No default width — callers size the box via `className` (e.g. `w-10` for a table thumbnail, `max-w-[200px]` for a detail hero); only the ratio/crop/placeholder behavior is this component's job.
    const shapeClass = `${ASPECT_CLASS[aspect]} overflow-hidden rounded-lg ${className}`;

    if (src === null) {
        // A visibly darker gray than the original gray-100/gray-300 pairing, which was close enough to white to read as a half-loaded image rather than an intentional "no photo" placeholder.
        return (
            <div className={`${shapeClass} bg-gray-200 flex items-center justify-center text-gray-400`}>
                <Icon name={placeholderIcon(category)} className="text-2xl" />
            </div>
        );
    }

    return (
        <div className={shapeClass}>
            <img src={src} alt={alt} className="w-full h-full object-cover" />
        </div>
    );
}
